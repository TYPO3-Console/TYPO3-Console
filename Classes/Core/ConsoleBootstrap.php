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

use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility;
use TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface;

/**
 * Class ConsoleBootstrap
 */
class ConsoleBootstrap extends Bootstrap {

	/**
	 * @var RequestHandlerInterface[]
	 */
	protected $requestHandlers = array();

	/**
	 * @var string $context Application context
	 */
	public function __construct($context) {
		self::$instance = $this;
		$this->requestId = uniqid();
		$this->applicationContext = new ApplicationContext($context);
		$this->baseSetup();
		$this->ensureRequiredEnvironment();
	}

	/**
	 *
	 */
	public function run() {
		$this->initializeClassLoader();
		$this->initializePackageManagement('Helhum\\Typo3Console\\Package\\UncachedPackageManager');

		$requestHandler = $this->resolveRequestHandler();
		$requestHandler->handleRequest();
	}

	/**
	 *
	 */
	protected function ensureRequiredEnvironment() {
		if (PHP_SAPI !== 'cli') {
			echo 'The comannd line must be executed with a cli PHP binary! The current PHP sapi type is "' . PHP_SAPI . '".' . PHP_EOL;
			exit(1);
		}
	}

	/**
	 * Initializes the package system and loads the package configuration and settings
	 * provided by the packages.
	 *
	 * @param string $packageManagerClassName Define an alternative package manager implementation (usually for the installer)
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function initializePackageManagement($packageManagerClassName) {
		require __DIR__ . '/../Package/UncachedPackageManager.php';
		/** @var \TYPO3\CMS\Core\Package\PackageManager $packageManager */
		$packageManager = new $packageManagerClassName();
		$this->setEarlyInstance('TYPO3\\Flow\\Package\\PackageManager', $packageManager);
		Utility\ExtensionManagementUtility::setPackageManager($packageManager);
		$packageManager->injectClassLoader($this->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader'));
//		$packageManager->injectCoreCache($this->getEarlyInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache('cache_core'));
		$packageManager->injectDependencyResolver(Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Package\\DependencyResolver'));
		$packageManager->initialize($this);
		Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Package\\PackageManager', $packageManager);
		return $this;
	}

	/**
	 * @param string $pathPart
	 * @return Bootstrap
	 */
	public function baseSetup($pathPart = '') {
		define('TYPO3_MODE', 'BE');
		define('TYPO3_cliMode', TRUE);
		$GLOBALS['MCONF']['name'] = '_CLI_lowlevel';
		class_alias(get_class($this), 'TYPO3\\Flow\\Core\\Bootstrap');
		parent::baseSetup($pathPart);
		// I want to see deprecation messages
		error_reporting(E_ALL & ~(E_STRICT | E_NOTICE));
		return $this;
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


	public function invokeRuntimeSequence() {
		$this->populateLocalConfiguration()
			->initializeCachingFramework()
			->initializeClassLoaderCaches();
		$this->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader')
			->setCacheIdentifier(md5_file(PATH_typo3conf . 'PackageStates.php'))
			->setPackages(
				$this->getEarlyInstance('TYPO3\\Flow\\Package\\PackageManager')->getActivePackages()
			);

		$this->defineDatabaseConstants()
			->defineUserAgentConstant()
			->registerExtDirectComponents()
			->transferDeprecatedCurlSettings()
			->setCacheHashOptions()
			->setDefaultTimezone()
			->initializeL10nLocales()
			->convertPageNotFoundHandlingToBoolean()
			->registerGlobalDebugFunctions()
			->setMemoryLimit()
			->defineTypo3RequestTypes()
			->loadTypo3LoadedExtAndExtLocalconf()
//			->initializeErrorHandling()
			->applyAdditionalConfigurationSettings()
			->initializeTypo3DbGlobal()
			->loadExtensionTables(TRUE)
			->initializeBackendUser()
			->initializeBackendAuthentication()
			->flushOutputBuffers();
//			->initializeBackendUserMounts()
//			->initializeLanguageObject();
	}

	protected function initializeErrorHandling() {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'] = '';
		$errorHandler = new \Helhum\Typo3Console\Error\ErrorHandler();
		$errorHandler->setExceptionalErrors(array(E_WARNING, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_STRICT, E_RECOVERABLE_ERROR));
		ini_set('display_errors', 1);
		if (((bool)ini_get('display_errors') && strtolower(ini_get('display_errors')) !== 'on' && strtolower(ini_get('display_errors')) !== '1') || !(bool)ini_get('display_errors')) {
			echo 'WARNING: Fatal errors will be suppressed due to your PHP config. You should consider enabling display_errors in your php.ini file!' . chr(10);
		}
		return $this;
	}

	protected function flushOutputBuffers() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::flushOutputBuffers();
		return $this;
	}
}