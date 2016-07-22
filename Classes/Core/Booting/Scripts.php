<?php
namespace Helhum\Typo3Console\Core\Booting;

/*
 * This file is part of the TYPO3 console project.
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
use Helhum\Typo3Console\Core\ConsoleBootstrap;
use Helhum\Typo3Console\Error\ErrorHandler;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class Scripts
 */
class Scripts
{
    /**
     * @var array
     */
    protected static $earlyCachesConfiguration = array();

    /**
     * @param ConsoleBootstrap $bootstrap
     */
    public static function initializeConfigurationManagement(ConsoleBootstrap $bootstrap)
    {
        $bootstrap->initializeConfigurationManagement();
        self::disableCachesForObjectManagement();
    }

    public static function disableCachesForObjectManagement()
    {
        $cacheConfigurations = &$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];
        foreach (
            array(
                'extbase_object',
                'extbase_reflection',
                'extbase_typo3dbbackend_tablecolumns',
                'extbase_typo3dbbackend_queries',
                'extbase_datamapfactory_datamap',
            ) as $id) {
            self::$earlyCachesConfiguration[$id] = $cacheConfigurations[$id];
            if (empty($cacheConfigurations[$id]['backend']) || $cacheConfigurations[$id]['backend'] === Typo3DatabaseBackend::class) {
                $cacheConfigurations[$id]['backend'] = FakeDatabaseBackend::class;
            } else {
                $cacheConfigurations[$id]['backend'] = NullBackend::class;
            }
            $cacheConfigurations[$id]['options'] = array();
        }
    }

    public static function initializeErrorHandling()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->setExceptionalErrors(array(E_WARNING, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_RECOVERABLE_ERROR));
        set_error_handler(array($errorHandler, 'handleError'));
        ini_set('display_errors', 1);
        if (((bool)ini_get('display_errors') && strtolower(ini_get('display_errors')) !== 'on' && strtolower(ini_get('display_errors')) !== '1') || !(bool)ini_get('display_errors')) {
            echo 'WARNING: Fatal errors will be suppressed due to your PHP config. You should consider enabling display_errors in your php.ini file!' . chr(10);
        }
    }

    /**
     * @param ConsoleBootstrap $bootstrap
     */
    public static function disableCoreCaches(ConsoleBootstrap $bootstrap)
    {
        $cacheConfigurations = &$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];
        foreach (
            array(
                'cache_core',
                'dbal',
            ) as $id) {
            if (isset($cacheConfigurations[$id])) {
                self::$earlyCachesConfiguration[$id] = $cacheConfigurations[$id];
            }
        }
        $bootstrap->disableCoreCaches();
    }

    /**
     * Reset the internal caches array in the object manager to
     * make it rebuild the caches with new configuration.
     *
     * @param ConsoleBootstrap $bootstrap
     */
    public static function reEnableOriginalCoreCaches(ConsoleBootstrap $bootstrap)
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] = array_replace_recursive($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'], self::$earlyCachesConfiguration);

        /** @var CacheManager $cacheManager */
        $cacheManager = $bootstrap->getEarlyInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
        $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);

        $reflectionObject = new \ReflectionObject($cacheManager);
        $property = $reflectionObject->getProperty('caches');
        $property->setAccessible(true);
        $property->setValue($cacheManager, array());
    }

    /**
     * @param ConsoleBootstrap $bootstrap
     */
    public static function initializeCachingFramework(ConsoleBootstrap $bootstrap)
    {
        $cacheManager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
        \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Cache\CacheManager::class, $cacheManager);

        $cacheFactory = new \TYPO3\CMS\Core\Cache\CacheFactory('production', $cacheManager);
        \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Cache\CacheFactory::class, $cacheFactory);

        $bootstrap->setEarlyInstance(\TYPO3\CMS\Core\Cache\CacheManager::class, $cacheManager);
    }

    /**
     * @param ConsoleBootstrap $bootstrap
     */
    public static function initializeDatabaseConnection(ConsoleBootstrap $bootstrap)
    {
        $bootstrap->initializeDatabaseConnection();
    }

    /**
     * @param ConsoleBootstrap $bootstrap
     */
    public static function initializeExtensionConfiguration(ConsoleBootstrap $bootstrap)
    {
        ExtensionManagementUtility::loadExtLocalconf();
        $bootstrap->applyAdditionalConfigurationSettings();
    }

    /**
     * @param ConsoleBootstrap $bootstrap
     */
    public static function initializePersistence(ConsoleBootstrap $bootstrap)
    {
        $bootstrap->loadExtensionTables();
    }

    /**
     * @param ConsoleBootstrap $bootstrap
     */
    public static function initializeAuthenticatedOperations(ConsoleBootstrap $bootstrap)
    {
        $bootstrap->initializeBackendUser();
        self::loadCommandLineBackendUser('_CLI_lowlevel');
        /** @var $backendUser \TYPO3\CMS\Core\Authentication\BackendUserAuthentication */
        $backendUser = $GLOBALS['BE_USER'];
        $backendUser->backendCheckLogin();
        // Global language object on CLI? rly? but seems to be needed by some scheduler tasks :(
        $bootstrap->initializeLanguageObject();
    }

    /**
     * If the backend script is in CLI mode, it will try to load a backend user named by the CLI module name (in lowercase)
     *
     * @param string $commandLineName the name of the module registered inside $TYPO3_CONF_VARS[SC_OPTIONS][GLOBAL][cliKeys] as second parameter
     * @throws \RuntimeException if a non-admin Backend user could not be loaded
     */
    protected static function loadCommandLineBackendUser($commandLineName)
    {
        if ($GLOBALS['BE_USER']->user['uid']) {
            throw new \RuntimeException('Another user was already loaded which is impossible in CLI mode!', 3);
        }
        if (!\TYPO3\CMS\Core\Utility\StringUtility::beginsWith($commandLineName, '_CLI_')) {
            throw new \RuntimeException('Module name, "' . $commandLineName . '", was not prefixed with "_CLI_"', 3);
        }
        $userName = strtolower($commandLineName);
        $GLOBALS['BE_USER']->setBeUserByName($userName);
        if (!$GLOBALS['BE_USER']->user['uid']) {
            throw new \RuntimeException('No backend user named "' . $userName . '" was found!', 3);
        }
        if ($GLOBALS['BE_USER']->isAdmin()) {
            throw new \RuntimeException('CLI backend user "' . $userName . '" was ADMIN which is not allowed!', 3);
        }
    }

    /**
     * Provide cleaned implementation of TYPO3 CMS core classes.
     * Can only be called *after* extension configuration is loaded (needs extbase configuration)!
     *
     * @param ConsoleBootstrap $bootstrap
     */
    public static function provideCleanClassImplementations(ConsoleBootstrap $bootstrap)
    {
        self::overrideImplementation(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, \Helhum\Typo3Console\Mvc\Controller\Argument::class);
        self::overrideImplementation(\TYPO3\CMS\Extbase\Command\HelpCommandController::class, \Helhum\Typo3Console\Command\HelpCommandController::class);
        self::overrideImplementation(\TYPO3\CMS\Extensionmanager\Command\ExtensionCommandController::class, \Helhum\Typo3Console\Command\ExtensionCommandController::class);
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\Helhum\Typo3Console\Property\TypeConverter\ArrayConverter::class);
    }

    /**
     * Tell Extbase, TYPO3 and PHP that we have another implementation
     */
    public static function overrideImplementation($originalClassName, $overrideClassName)
    {
        /** @var $extbaseObjectContainer \TYPO3\CMS\Extbase\Object\Container\Container */
        $extbaseObjectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class);
        $extbaseObjectContainer->registerImplementation($originalClassName, $overrideClassName);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][$originalClassName]['className'] = $overrideClassName;
        class_alias($overrideClassName, $originalClassName);
    }
}
