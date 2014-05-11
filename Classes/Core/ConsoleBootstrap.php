<?php
namespace Helhum\Typo3Console\Core;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Core\Booting\Sequence;
use Helhum\Typo3Console\Mvc\Cli\CommandManager;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Frontend\StringFrontend;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility;
use TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface;

/**
 * Class ConsoleBootstrap
 */
class ConsoleBootstrap extends Bootstrap {

	/**
	 * @var array
	 */
	public $commands = array();

	/**
	 * @var RequestHandlerInterface[]
	 */
	protected $requestHandlers = array();

	/**
	 * @var RunLevel
	 */
	protected $runLevel;

	/**
	 * @var string $context Application context
	 */
	public function __construct($context) {
		self::$instance = $this;
		$this->ensureRequiredEnvironment();
		$this->applicationContext = new ApplicationContext($context);
		$this->baseSetup();
		$this->requireBaseClasses();
		$this->defineTypo3RequestTypes();

		$this->requestId = uniqid();
		$this->runLevel = new RunLevel();
	}

	/**
	 * Bootstraps the minimal infrastructure, resolves a fitting request handler and
	 * then passes control over to that request handler.
	 */
	public function run() {
		$this->initializeClassLoader();
		$this->initializeCommandManager();
		$this->initializePackageManagement();

		$requestHandler = $this->resolveRequestHandler();
		$requestHandler->handleRequest();
	}

	/**
	 * Builds the sequence for the given run level
	 *
	 * @param $commandIdentifier
	 * @return Sequence
	 */
	public function buildBootingSequenceForCommand($commandIdentifier) {
		return $this->runLevel->buildSequenceForCommand($commandIdentifier);
	}

	/**
	 * Sets a run level for a specific command
	 *
	 * @param $commandIdentifier
	 * @param $runLevel
	 * @api
	 */
	public function setRunLevelForCommand($commandIdentifier, $runLevel) {
		$this->runLevel->setRunLevelForCommand($commandIdentifier, $runLevel);
	}

	/**
	 * Adds a step to the resolved booting sequence
	 *
	 * @param string $commandIdentifier
	 * @param string $stepIdentifier
	 */
	public function addBootingStepForCommand($commandIdentifier, $stepIdentifier) {
		$this->runLevel->addBootingStepForCommand($commandIdentifier, $stepIdentifier);
	}

	/**
	 * Checks PHP sapi type and sets required PHP options
	 */
	protected function ensureRequiredEnvironment() {
		if (PHP_SAPI !== 'cli') {
			echo 'The comannd line must be executed with a cli PHP binary! The current PHP sapi type is "' . PHP_SAPI . '".' . PHP_EOL;
			exit(1);
		}
		ini_set('memory_limit', -1);
		set_time_limit(0);
	}

	/**
	 * Registers a request handler which can possibly handle a request.
	 *
	 * All registered request handlers will be queried if they can handle a request
	 * when the bootstrap's run() method is called.
	 *
	 * @param RequestHandlerInterface $requestHandler
	 * @return void
	 * @api
	 */
	public function registerRequestHandler(RequestHandlerInterface $requestHandler) {
		$this->requestHandlers[get_class($requestHandler)] = $requestHandler;
	}

	/**
	 * Returns the command manager which can be used to register commands during package management initialisation
	 *
	 * @return CommandManager
	 * @api
	 */
	public function getCommandManager() {
		return $this->getEarlyInstance('TYPO3\CMS\Extbase\Mvc\Cli\CommandManager');
	}

	/**
	 * Iterates over the registered request handlers and determines which one fits best.
	 *
	 * @return RequestHandlerInterface A request handler
	 * @throws \TYPO3\CMS\Core\Error\Exception
	 */
	protected function resolveRequestHandler() {
		foreach ($this->requestHandlers as $requestHandler) {
			if ($requestHandler->canHandleRequest() > 0) {
				$priority = $requestHandler->getPriority();
				if (isset($suitableRequestHandlers[$priority])) {
					throw new \TYPO3\CMS\Core\Error\Exception('More than one request handler with the same priority can handle the request, but only one handler may be active at a time!', 1176475350);
				}
				$suitableRequestHandlers[$priority] = $requestHandler;
			}
		}
		ksort($suitableRequestHandlers);
		return array_pop($suitableRequestHandlers);
	}



	/**
	 * Complete bootstrap in traditional order and with no possibility to inject steps
	 */
	public function runLegacyBootstrap() {
		$this->initializeConfigurationManagement();
		$this->defineDatabaseConstants();
		$this->initializeCachingFramework();
		\Helhum\Typo3Console\Core\Booting\Scripts::initializeClassLoaderCaches($this);
		$this->registerExtDirectComponents();
		$this->transferDeprecatedCurlSettings();
		$this->setCacheHashOptions();
		$this->initializeL10nLocales();
		$this->convertPageNotFoundHandlingToBoolean();
		$this->registerGlobalDebugFunctions();
		$this->setMemoryLimit();
		$this->loadTypo3LoadedExtAndExtLocalconf();
		$this->initializeErrorHandling();
		$this->applyAdditionalConfigurationSettings();
		$this->initializeTypo3DbGlobal();
		$this->loadExtensionTables();
		$this->initializeBackendUser();
		$this->initializeBackendAuthentication();
		$this->initializeBackendUserMounts();
		$this->initializeLanguageObject();
		$this->flushOutputBuffers();
	}

	/*
	 *  Additional Methods needed for the bootstrap sequences
	 */

	/**
	 */
	public function initializeCommandManager() {
		$commandManager = Utility\GeneralUtility::makeInstance('Helhum\Typo3Console\Mvc\Cli\CommandManager');
		$this->setEarlyInstance('TYPO3\CMS\Extbase\Mvc\Cli\CommandManager', $commandManager);
		Utility\GeneralUtility::setSingletonInstance('TYPO3\CMS\Extbase\Mvc\Cli\CommandManager', $commandManager);
	}

	/**
	 * @param string $pathPart
	 * @return void
	 */
	public function baseSetup($pathPart = '') {
		define('TYPO3_MODE', 'BE');
		define('TYPO3_cliMode', TRUE);
		$GLOBALS['MCONF']['name'] = '_CLI_lowlevel';
		class_alias(get_class($this), 'TYPO3\\Flow\\Core\\Bootstrap');
		parent::baseSetup($pathPart);
		// I want to see deprecation messages
		error_reporting(E_ALL & ~(E_STRICT | E_NOTICE));

	}

	/**
	 * Classes required prior to class loader
	 */
	protected function requireBaseClasses() {
		require_once PATH_site . 'typo3/sysext/core/Classes/Exception.php';
		require_once PATH_site . 'typo3/sysext/extbase/Classes/Mvc/Cli/CommandManager.php';
		require_once PATH_site . 'typo3/sysext/extbase/Classes/Mvc/RequestHandlerInterface.php';

		require_once __DIR__ . '/../Error/ErrorHandler.php';
		require_once __DIR__ . '/../Mvc/Cli/RequestHandler.php';
		require_once __DIR__ . '/Booting/Sequence.php';
		require_once __DIR__ . '/Booting/Step.php';
		require_once __DIR__ . '/Booting/Scripts.php';
		require_once __DIR__ . '/Booting/RunLevel.php';
		require_once __DIR__ . '/../Mvc/Cli/CommandManager.php';
	}

	/**
	 * Initializes the package system and loads the package configuration and settings
	 * provided by the packages.
	 *
	 * @param string $packageManagerClassName Define an alternative package manager implementation (usually for the installer)
	 * @return void
	 */
	public function initializePackageManagement($packageManagerClassName = 'Helhum\\Typo3Console\\Package\\UncachedPackageManager') {
		require __DIR__ . '/../Package/UncachedPackageManager.php';
		$packageManager = new \Helhum\Typo3Console\Package\UncachedPackageManager();
		$this->setEarlyInstance('TYPO3\\Flow\\Package\\PackageManager', $packageManager);
		Utility\ExtensionManagementUtility::setPackageManager($packageManager);
		$packageManager->injectClassLoader($this->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader'));
		$packageManager->injectDependencyResolver(Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Package\\DependencyResolver'));
		$packageManager->initialize($this);
		Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Package\\PackageManager', $packageManager);
	}

	public function disableObjectCaches() {
		$this->disableCoreAndClassesCache();
		$this->initializeUncachedClassLoader();
		$this->disableCachesForObjectManagement();
	}

	/**
	 *
	 */
	protected function initializeUncachedClassLoader() {
		$this->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader')
			->injectClassesCache(new StringFrontend('cache_classes', new TransientMemoryBackend($this->getApplicationContext())));
		$this->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader')
			->setPackages($this->getEarlyInstance('TYPO3\\Flow\\Package\\PackageManager')->getActivePackages());
	}

	protected function disableCachesForObjectManagement() {
		$cacheConfigurations = &$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];

		$cacheConfigurations['extbase_typo3dbbackend_tablecolumns'] = array(
			'groups' => array('system'),
			'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend'
		);
		$cacheConfigurations['extbase_typo3dbbackend_queries'] = array(
			'groups' => array('system'),
			'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend'
		);
		$cacheConfigurations['extbase_datamapfactory_datamap'] = array(
			'groups' => array('system'),
			'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend'
		);
		$cacheConfigurations['extbase_object']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
		$cacheConfigurations['extbase_reflection']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';

		/** @var PackageManager $packageManager */
		$packageManager = $this->getEarlyInstance('TYPO3\\Flow\\Package\\PackageManager');
		if ($packageManager->isPackageActive('dbal')) {
			$cacheConfigurations['dbal'] = array(
				'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\TransientMemoryBackend',
				'groups' => array()
			);
		}
	}

	public function initializeConfigurationManagement() {
		$this->populateLocalConfiguration();
		$this->setDefaultTimezone();
		$this->defineUserAgentConstant();
	}

	public function initializeDatabaseConnection() {
		$this->defineDatabaseConstants();
		$this->initializeTypo3DbGlobal();
	}

	/**
	 *
	 */
	public function initializeErrorHandling() {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'] = '';
		$errorHandler = new \Helhum\Typo3Console\Error\ErrorHandler();
//		$errorHandler->setExceptionalErrors(array(E_WARNING, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_STRICT, E_RECOVERABLE_ERROR));
		$errorHandler->setExceptionalErrors(array(E_WARNING, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_RECOVERABLE_ERROR));
		ini_set('display_errors', 1);
		if (((bool)ini_get('display_errors') && strtolower(ini_get('display_errors')) !== 'on' && strtolower(ini_get('display_errors')) !== '1') || !(bool)ini_get('display_errors')) {
			echo 'WARNING: Fatal errors will be suppressed due to your PHP config. You should consider enabling display_errors in your php.ini file!' . chr(10);
		}
	}

	/**
	 * @return void
	 */
	protected function flushOutputBuffers() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::flushOutputBuffers();
	}
}