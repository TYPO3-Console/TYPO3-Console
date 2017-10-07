<?php
namespace Helhum\Typo3Console\Core\Booting;

/*
 * This file is part of the TYPO3 Console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Helhum\Typo3Console\Core\Cache\FakeDatabaseBackend;
use Helhum\Typo3Console\Error\ErrorHandler;
use Helhum\Typo3Console\Error\ExceptionHandler;
use Helhum\Typo3Console\Package\UncachedPackageManager;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Scripts
{
    /**
     * @var array
     */
    private static $earlyCachesConfiguration = [];

    /**
     * @param Bootstrap $bootstrap
     */
    public static function initializeConfigurationManagement(Bootstrap $bootstrap)
    {
        $bootstrap->populateLocalConfiguration();
        \Closure::bind(function () use ($bootstrap) {
            if (!Bootstrap::usesComposerClassLoading()) {
                $bootstrap->initializeRuntimeActivatedPackagesFromConfiguration();
            }
            $bootstrap->setDefaultTimezone();
        }, null, $bootstrap)();
        CompatibilityScripts::initializeConfigurationManagement($bootstrap);
        self::disableCachesForObjectManagement();
    }

    public static function baseSetup(Bootstrap $bootstrap)
    {
        define('TYPO3_MODE', 'BE');
        define('PATH_site', \TYPO3\CMS\Core\Utility\GeneralUtility::fixWindowsFilePath(getenv('TYPO3_PATH_ROOT')) . '/');
        define('PATH_thisScript', PATH_site . 'typo3/index.php');

        $bootstrap->setRequestType(TYPO3_REQUESTTYPE_BE | TYPO3_REQUESTTYPE_CLI);
        $bootstrap->baseSetup();
        // I want to see deprecation messages
        error_reporting(E_ALL & ~(E_STRICT | E_NOTICE));
        $exceptionHandler = new ExceptionHandler();
        set_exception_handler([$exceptionHandler, 'handleException']);

        self::initializePackageManagement($bootstrap);
    }

    /**
     * Initializes the package system and loads the package configuration and settings
     * provided by the packages.
     *
     * @return void
     */
    private static function initializePackageManagement(Bootstrap $bootstrap)
    {
        $packageManager = new UncachedPackageManager();
        $bootstrap->setEarlyInstance(PackageManager::class, $packageManager);
        ExtensionManagementUtility::setPackageManager($packageManager);
        $dependencyResolver = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Package\DependencyResolver::class);
        $dependencyResolver->injectDependencyOrderingService(
            GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\DependencyOrderingService::class)
        );
        $packageManager->injectDependencyResolver($dependencyResolver);
        $packageManager->init();
        GeneralUtility::setSingletonInstance(PackageManager::class, $packageManager);
    }

    public static function disableCachesForObjectManagement()
    {
        $cacheConfigurations = &$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];
        foreach (
            [
                'extbase_object',
                'extbase_reflection',
                'extbase_typo3dbbackend_tablecolumns',
                'extbase_typo3dbbackend_queries',
                'extbase_datamapfactory_datamap',
            ] as $id) {
            if (!isset($cacheConfigurations[$id])) {
                continue;
            }
            self::$earlyCachesConfiguration[$id] = $cacheConfigurations[$id];
            if (empty($cacheConfigurations[$id]['backend']) || $cacheConfigurations[$id]['backend'] === Typo3DatabaseBackend::class) {
                $cacheConfigurations[$id]['backend'] = FakeDatabaseBackend::class;
            } else {
                $cacheConfigurations[$id]['backend'] = NullBackend::class;
            }
            $cacheConfigurations[$id]['options'] = [];
        }
    }

    public static function initializeErrorHandling()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->setExceptionalErrors([E_WARNING, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_RECOVERABLE_ERROR]);
        set_error_handler([$errorHandler, 'handleError']);
        ini_set('display_errors', 1);
        if (((bool)ini_get('display_errors') && strtolower(ini_get('display_errors')) !== 'on' && strtolower(ini_get('display_errors')) !== '1') || !(bool)ini_get('display_errors')) {
            echo 'WARNING: Fatal errors will be suppressed due to your PHP config. You should consider enabling display_errors in your php.ini file!' . chr(10);
        }
    }

    /**
     * @param Bootstrap $bootstrap
     */
    public static function disableCoreCaches(Bootstrap $bootstrap)
    {
        $cacheConfigurations = &$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];
        self::$earlyCachesConfiguration['cache_core'] = $cacheConfigurations['cache_core'];
        $bootstrap->disableCoreCache();
    }

    /**
     * Reset the internal caches array in the object manager to
     * make it rebuild the caches with new configuration.
     *
     * @param Bootstrap $bootstrap
     */
    public static function reEnableOriginalCoreCaches(Bootstrap $bootstrap)
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] = array_replace_recursive($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'], self::$earlyCachesConfiguration);

        /** @var CacheManager $cacheManager */
        $cacheManager = $bootstrap->getEarlyInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
        $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);

        $reflectionObject = new \ReflectionObject($cacheManager);
        $property = $reflectionObject->getProperty('caches');
        $property->setAccessible(true);
        $property->setValue($cacheManager, []);
    }

    /**
     * @param Bootstrap $bootstrap
     */
    public static function initializeCachingFramework(Bootstrap $bootstrap)
    {
        $cacheManager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
        \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Cache\CacheManager::class, $cacheManager);
        $bootstrap->setEarlyInstance(\TYPO3\CMS\Core\Cache\CacheManager::class, $cacheManager);
    }

    /**
     * @param Bootstrap $bootstrap
     */
    public static function initializeExtensionConfiguration(Bootstrap $bootstrap)
    {
        CompatibilityScripts::initializeExtensionConfiguration($bootstrap);
        ExtensionManagementUtility::loadExtLocalconf();
        $bootstrap->setFinalCachingFrameworkCacheConfiguration();
        $bootstrap->unsetReservedGlobalVariables();
        ExtensionManagementUtility::loadBaseTca();
    }

    /**
     * @param Bootstrap $bootstrap
     */
    public static function initializeAuthenticatedOperations(Bootstrap $bootstrap)
    {
        $bootstrap->loadExtTables();
        $bootstrap->initializeBackendUser(CommandLineUserAuthentication::class);
        self::loadCommandLineBackendUser();
        // Global language object on CLI? rly? but seems to be needed by some scheduler tasks :(
        $bootstrap->initializeLanguageObject();
    }

    /**
     * If the backend script is in CLI mode, it will try to load a backend user named _cli_lowlevel
     *
     * @throws \RuntimeException if a non-admin Backend user could not be loaded
     */
    private static function loadCommandLineBackendUser()
    {
        /** @var CommandLineUserAuthentication $backendUser */
        $backendUser = $GLOBALS['BE_USER'];
        if ($backendUser->user['uid']) {
            throw new \RuntimeException('Another user was already loaded which is impossible in CLI mode!', 3);
        }
        $backendUser->authenticate();
    }

    /**
     * Provide cleaned implementation of TYPO3 core classes.
     * Can only be called *after* extension configuration is loaded (needs extbase configuration)!
     */
    public static function provideCleanClassImplementations()
    {
        self::overrideImplementation(\TYPO3\CMS\Extbase\Command\HelpCommandController::class, \Helhum\Typo3Console\Command\HelpCommandController::class);
        self::overrideImplementation(\TYPO3\CMS\Extbase\Mvc\Cli\Command::class, \Helhum\Typo3Console\Mvc\Cli\Command::class);
        if (empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'] = [];
        }
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'][] = \Helhum\Typo3Console\Property\TypeConverter\ArrayConverter::class;
    }

    /**
     * Tell Extbase, TYPO3 and PHP that we have another implementation
     *
     * @param string $originalClassName
     * @param string $overrideClassName
     */
    public static function overrideImplementation($originalClassName, $overrideClassName)
    {
        self::registerImplementation($originalClassName, $overrideClassName);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][$originalClassName]['className'] = $overrideClassName;
        class_alias($overrideClassName, $originalClassName);
    }

    /**
     * Tell Extbase about this implementation
     *
     * @param string $className
     * @param string $alternativeClassName
     */
    private static function registerImplementation($className, $alternativeClassName)
    {
        /** @var $extbaseObjectContainer \TYPO3\CMS\Extbase\Object\Container\Container */
        $extbaseObjectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class);
        $extbaseObjectContainer->registerImplementation($className, $alternativeClassName);
    }
}
