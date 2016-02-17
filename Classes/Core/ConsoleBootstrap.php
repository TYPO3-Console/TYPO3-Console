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
use Helhum\Typo3Console\Core\Booting\Scripts;
use Helhum\Typo3Console\Core\Booting\Sequence;
use Helhum\Typo3Console\Error\ExceptionHandler;
use Helhum\Typo3Console\Mvc\Cli\CommandManager;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Frontend\StringFrontend;
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
	 * @deprecated with 6.2 will be removed with 7
	 * @var string
	 */
	protected $packageManagerInstanceName = 'TYPO3\\Flow\\Package\\PackageManager';

	/**
	 * @var string $context Application context
	 */
	public function __construct($context) {
		self::$instance = $this;
		$this->ensureRequiredEnvironment();
		parent::__construct($context);
	}

	/**
	 * Override parent to calrify return type
	 *
	 * @return ConsoleBootstrap
	 */
	static public function getInstance() {
		return parent::getInstance();
	}

	/**
	 * Bootstraps the minimal infrastructure, resolves a fitting request handler and
	 * then passes control over to that request handler.
	 * @return ConsoleBootstrap
	 */

	/**
	 * @param \Composer\Autoload\ClassLoader|NULL $classLoader
	 * @return $this
	 * @throws \TYPO3\CMS\Core\Error\Exception
	 */
	public function run($classLoader = NULL) {
		// @deprecated in 6.2, will be removed in 7.0 (condition will be removed)
		if ($classLoader) {
			$this->initializeClassLoader($classLoader);
		}
		if (is_callable(array($this, 'setRequestType'))) {
			$this->defineTypo3RequestTypes();
			$this->setRequestType(TYPO3_REQUESTTYPE_BE | TYPO3_REQUESTTYPE_CLI);
		}
		$this->baseSetup();
		$this->requireBaseClasses();
		if (!is_callable(array($this, 'setRequestType'))) {
			$this->defineTypo3RequestTypes();
		}
		$this->requestId = uniqid();
		$this->runLevel = new RunLevel();
		$this->setEarlyInstance('Helhum\Typo3Console\Core\Booting\RunLevel', $this->runLevel);
		new ExceptionHandler();

		// @deprecated in 6.2, will be removed in 7.0
		if (!$classLoader) {
			$this->initializeClassLoader(NULL);
		}
		$this->initializeCommandManager();
		$this->initializePackageManagement();

		$requestHandler = $this->resolveCliRequestHandler();
		$requestHandler->handleRequest();
		return $this;
	}

	/**
	 * TODO: Add other API that does not depend on bootstrap
	 *
	 * @param string $runLevel
	 */
	public function requestRunLevel($runLevel) {
		$sequence = $this->runLevel->buildDifferentialSequenceUpToLevel($runLevel);
		$sequence->invoke($this);
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
			echo 'The command line must be executed with a cli PHP binary! The current PHP sapi type is "' . PHP_SAPI . '".' . PHP_EOL;
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
	public function resolveCliRequestHandler() {
		if (empty($this->requestHandlers)) {
			throw new \InvalidArgumentException('No request handlers found. Make sure the extension typo3_console is active and try again.', 1417863425);
		}
		$suitableRequestHandlers = array();
		foreach ($this->requestHandlers as $requestHandler) {
			if ($requestHandler->canHandleRequest() > 0) {
				$priority = $requestHandler->getPriority();
				if (isset($suitableRequestHandlers[$priority])) {
					throw new \TYPO3\CMS\Core\Error\Exception('More than one request handler with the same priority can handle the request, but only one handler may be active at a time!', 1176475350);
				}
				$suitableRequestHandlers[$priority] = $requestHandler;
			}
		}
		if (empty($suitableRequestHandlers)) {
			throw new \InvalidArgumentException('No request handler found that can handle that request.', 1417863426);
		}
		ksort($suitableRequestHandlers);
		return array_pop($suitableRequestHandlers);
	}

	/*
	 *  Additional Methods needed for the bootstrap sequences
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
		// @deprecated in 6.2 will be removed in 7
		if (is_callable(array(__CLASS__, 'initializeComposerClassLoader'))) {
			$this->setEarlyInstance('Composer\\Autoload\\ClassLoader', self::initializeComposerClassLoader());
		}
		// @deprecated in 6.2 will be removed in 7
		if (!class_exists('TYPO3\\Flow\\Package\\PackageManager')) {
			$this->packageManagerInstanceName = 'TYPO3\\CMS\\Core\\Package\\PackageManager';
			class_alias('TYPO3\\CMS\\Core\\Package\\PackageManager', 'TYPO3\\Flow\\Package\\PackageManager');
		}
		// @deprecated in 6.2 will be removed in 7
		class_alias('TYPO3\\CMS\\Core\\Core\\Bootstrap', 'TYPO3\\Flow\\Core\\Bootstrap');

		define('TYPO3_MODE', 'BE');
		define('TYPO3_cliMode', TRUE);
		$GLOBALS['MCONF']['name'] = '_CLI_lowlevel';
		parent::baseSetup($pathPart);
		// I want to see deprecation messages
//		error_reporting(E_ALL & ~(E_STRICT | E_NOTICE));
		// I would love to see deprecation messages, but unfortunately TYPO3 core itself triggers such messages :(
		error_reporting(E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED));

	}

	/**
	 * Classes required prior to class loader
	 */
	protected function requireBaseClasses() {
		require_once PATH_site . 'typo3/sysext/core/Classes/Exception.php';
		require_once PATH_site . 'typo3/sysext/extbase/Classes/Mvc/Cli/CommandManager.php';
		require_once PATH_site . 'typo3/sysext/extbase/Classes/Mvc/RequestHandlerInterface.php';

		require_once __DIR__ . '/../Error/ErrorHandler.php';
		require_once __DIR__ . '/../Error/ExceptionHandler.php';
		require_once __DIR__ . '/../Mvc/Cli/RequestHandler.php';
		require_once __DIR__ . '/Booting/Sequence.php';
		require_once __DIR__ . '/Booting/Step.php';
		require_once __DIR__ . '/Booting/Scripts.php';
		require_once __DIR__ . '/Booting/RunLevel.php';
		require_once __DIR__ . '/../Mvc/Cli/CommandManager.php';
		if (!interface_exists('Symfony\\Component\\Console\\Output\\OutputInterface')) {
			require_once __DIR__ . '/../../Libraries/symfony-console.phar';
		}
		if (!class_exists('Symfony\\Component\\Process\\Process')) {
			require_once __DIR__ . '/../../Libraries/symfony-process.phar';
		}
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
		$this->setEarlyInstance($this->packageManagerInstanceName, $packageManager);
		Utility\ExtensionManagementUtility::setPackageManager($packageManager);
		// @deprecated in 6.2, will be removed in 7.0
		if (is_callable(array($packageManager, 'injectClassLoader'))) {
			$packageManager->injectClassLoader($this->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader'));
		}
		$dependencyResolver = Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Package\\DependencyResolver');
		// required since 7.4
		if (is_callable(array($dependencyResolver, 'injectDependencyOrderingService'))) {
			$dependencyResolver->injectDependencyOrderingService(
				Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Service\\DependencyOrderingService')
			);
		}
		$packageManager->injectDependencyResolver($dependencyResolver);
		$packageManager->init($this);
		Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Package\\PackageManager', $packageManager);
	}

	public function disableCoreCaches() {
		// @deprecated in 6.2, will be removed in 7.0
		if (is_callable(array(__CLASS__, 'disableCoreAndClassesCache'))) {
			$this->disableCoreAndClassesCache();
			$this->initializeUncachedClassLoader();
		} else {
			$this->disableCoreCache();
		}
		/** @var PackageManager $packageManager */
		$packageManager = $this->getEarlyInstance($this->packageManagerInstanceName);
		if ($packageManager->isPackageActive('dbal')) {
			$cacheConfigurations['dbal'] = array(
				'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\TransientMemoryBackend',
				'groups' => array()
			);
		}
	}

	protected function initializeUncachedClassLoader() {
		$classLoader = $this->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader');
		$classLoader->injectClassesCache(new StringFrontend('cache_classes', new TransientMemoryBackend($this->getApplicationContext())));

		$reflectionObject = new \ReflectionObject($classLoader);
		$property = $reflectionObject->getProperty('isLoadingLocker');
		$property->setAccessible(TRUE);
		$property->setValue($classLoader, TRUE);

		$classLoader->setPackages($this->getEarlyInstance($this->packageManagerInstanceName)->getActivePackages());
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

	protected function flushOutputBuffers() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::flushOutputBuffers();
	}


	/**
	 * Sets up additional configuration applied in all scopes
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 * @todo: This is only for master "compatibility". Once 6.2 compatibility is removed, we can call these methods directly.
	 */
	public function applyAdditionalConfigurationSettings() {
		if (is_callable(array($this, 'initializeErrorHandling'))) {
			$this->initializeErrorHandling();
		} else {
			$this->initializeExceptionHandling();
		}
		$this->setFinalCachingFrameworkCacheConfiguration()
			->defineLoggingAndExceptionConstants()
			->unsetReservedGlobalVariables();
		return $this;
	}

}
