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

use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Frontend\StringFrontend;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility;
use TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface;

/**
 * Class ConsoleBootstrap
 */
class ConsoleBootstrap extends Bootstrap {

	const RUNLEVEL_COMPILE = -1;
	const RUNLEVEL_BASIC_RUNTIME = 1;
	const RUNLEVEL_EXTENDED_RUNTIME = 2;
	const RUNLEVEL_LEGACY = 10;

	protected $sequences = array(
		0 => 'invokeEssentialSequence',
		self::RUNLEVEL_COMPILE => 'invokeCompiletimeSequence',
		self::RUNLEVEL_BASIC_RUNTIME => 'invokeBasicRuntimeSequence',
		self::RUNLEVEL_EXTENDED_RUNTIME => 'invokeExtendedRuntimeSequence',
	);

	/**
	 * @var array
	 */
	protected $commands = array();

	/**
	 * @var RequestHandlerInterface[]
	 */
	protected $requestHandlers = array();

	/**
	 * @var array
	 */
	protected $namespacePrefixes = array();

	/**
	 * @var string $context Application context
	 */
	public function __construct($context) {
		self::$instance = $this;
		$this->requestId = uniqid();
		$this->applicationContext = new ApplicationContext($context);
		$this->baseSetup();
		$this->ensureRequiredEnvironment();
		$this->defineTypo3RequestTypes();
	}

	/**
	 * Bootstraps the minimal infrastructure, resolves a fitting request handler and
	 * then passes control over to that request handler.
	 */
	public function run() {
		$this->initializeClassLoader();
		$this->initializePackageManagement();

		$requestHandler = $this->resolveRequestHandler();
		$requestHandler->handleRequest();
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
	 * @param string $commandIdentifier
	 * @param int $runLevel
	 */
	public function registerCommandForRunLevel($commandIdentifier, $runLevel) {
		if (isset($this->sequences[$runLevel])) {
			$this->commands[$commandIdentifier] = array(
				'runLevel' => $runLevel,
				'controllerClassName' => $this->getControllerClassNameFromCommandIdentifier($commandIdentifier),
			);
		} else {
			echo 'ERROR: Invalid runLevel!' . PHP_EOL;
			exit(1);
		}
	}

	protected function getControllerClassNameFromCommandIdentifier($commandIdentifier) {
		list($packageKey, $controllerName, $commandName) = explode(':', $commandIdentifier);
		$package = $this->getEarlyInstance('TYPO3\\Flow\\Package\\PackageManager')->getPackage($packageKey);
		return $package->getNamespace() . '\\Command\\' . ucfirst($controllerName) . 'CommandController';
	}


	public function getRunlevelForCommand($commandIdentifier) {
		$commandIdentifierParts = explode(':', $commandIdentifier);
		if (count($commandIdentifierParts) === 2) {
			$commandControllerName = $commandIdentifierParts[0];
		} else {
			$commandPackageName = $commandIdentifierParts[0];
			$commandControllerName = $commandIdentifierParts[1];
		}
//		if (count($commandIdentifierParts) !== 3) {
//			return FALSE;
//		}
		if (isset($this->commands[$commandIdentifier])) {
			return $this->commands[$commandIdentifier]['runLevel'];
		}

		foreach ($this->commands as $fullControllerIdentifier => $commandRegistry) {
			list($packageKey, $controllerName, $commandName) = explode(':', $fullControllerIdentifier);
			if ($controllerName === $commandControllerName) {
				return $this->commands[$fullControllerIdentifier]['runLevel'];
			}
		}

		return self::RUNLEVEL_LEGACY;
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


	public function invokeSequence($runLevel) {
		if (isset($this->sequences[$runLevel])) {
			$this->{$this->sequences[$runLevel]}();
		} else {
			echo 'RUNLEVEL INVOCATION FAILED!' . PHP_EOL;
			exit(1);
		}
	}

	/**
	 * Runlevel 0
	 */
	public function invokeEssentialSequence() {
		$this->initializeConfigurationManagement();
		$this->initializeCachingFramework();
		$this->initializeErrorHandling();
	}


	/**
	 * Runlevel -1
	 */
	public function invokeCompiletimeSequence() {
		$this->initializeUncachedClassLoader();
	}

	/**
	 * Runlevel 1
	 */
	public function invokeBasicRuntimeSequence() {
		$this->initializeClassLoaderCaches();
		$this->registerGlobalDebugFunctions();
		$this->loadTypo3LoadedExtAndExtLocalconf();
		$this->applyAdditionalConfigurationSettings();
		$this->defineDatabaseConstants();
		$this->initializeTypo3DbGlobal();
	}

	/**
	 * Runlevel 2
	 */
	public function invokeExtendedRuntimeSequence() {
		$this->initializePersistence();
		$this->initializeAuthenticatedOperations();
	}

	/**
	 * Runlevel 10
	 */
	public function invokeLegacySequence() {
		$this->populateLocalConfiguration();
		$this->setDefaultTimezone();
		$this->defineUserAgentConstant();
		$this->defineDatabaseConstants();
		$this->initializeCachingFramework();
		$this->initializeClassLoaderCaches();
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


	protected function initializeConfigurationManagement() {
		$this->populateLocalConfiguration();
		$this->setDefaultTimezone();
		$this->defineUserAgentConstant();
		foreach ($this->commands as $identifier => $commandRegistry) {
			if (!isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][$commandRegistry['controllerClassName']])) {
				$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][$commandRegistry['controllerClassName']] = $commandRegistry['controllerClassName'];
			}
		}

	}

	protected function initializePersistence() {
		$this->loadExtensionTables();
	}

	protected function initializeAuthenticatedOperations() {
		$this->initializeBackendUser();
		$this->initializeBackendAuthentication();
		$this->initializeBackendUserMounts();
	}

	/**
	 * @return void
	 */
	public function initializeClassLoaderCaches() {
		parent::initializeClassLoaderCaches();
		$this->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader')
			->setCacheIdentifier(md5_file(PATH_typo3conf . 'PackageStates.php'))
			->setPackages($this->getEarlyInstance('TYPO3\\Flow\\Package\\PackageManager')->getActivePackages());
	}

	/**
	 * @return void
	 */
	public function initializeUncachedClassLoader() {
		parent::initializeClassLoaderCaches();
		$this->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader')
			->injectClassesCache(new StringFrontend('cache_classes', new TransientMemoryBackend($this->getApplicationContext())))
			->setPackages($this->getEarlyInstance('TYPO3\\Flow\\Package\\PackageManager')->getActivePackages());
	}

	/**
	 * @return void
	 */
	protected function initializeErrorHandling() {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'] = '';
		$errorHandler = new \Helhum\Typo3Console\Error\ErrorHandler();
		$errorHandler->setExceptionalErrors(array(E_WARNING, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_STRICT, E_RECOVERABLE_ERROR));
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