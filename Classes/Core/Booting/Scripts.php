<?php
namespace Helhum\Typo3Console\Core\Booting;

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

use Helhum\Typo3Console\Core\ConsoleBootstrap;
use Helhum\Typo3Console\Error\ErrorHandler;
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
            $cacheConfigurations[$id]['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
            $cacheConfigurations[$id]['options'] = array();
        }
    }

    /**
     * @param ConsoleBootstrap $bootstrap
     */
    public static function initializeErrorHandling(ConsoleBootstrap $bootstrap)
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'] = '';
        $errorHandler = new ErrorHandler();
//      $errorHandler->setExceptionalErrors(array(E_WARNING, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_STRICT, E_RECOVERABLE_ERROR));
        $errorHandler->setExceptionalErrors(array(E_WARNING, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_RECOVERABLE_ERROR));
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
                'cache_classes',
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
        $cacheManager = $bootstrap->getEarlyInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager');
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
        // Cache framework initialisation for TYPO3 CMS <= 7.3
        if (class_exists('TYPO3\\CMS\\Core\\Cache\\Cache')) {
            $bootstrap->setEarlyInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager', \TYPO3\CMS\Core\Cache\Cache::initializeCachingFramework());
            // @deprecated since 6.2 will be removed in two versions
            if (class_exists('TYPO3\\CMS\\Core\\Compatibility\\GlobalObjectDeprecationDecorator')) {
                $GLOBALS['typo3CacheManager'] = new \TYPO3\CMS\Core\Compatibility\GlobalObjectDeprecationDecorator('TYPO3\\CMS\\Core\\Cache\\CacheManager');
                $GLOBALS['typo3CacheFactory'] = new \TYPO3\CMS\Core\Compatibility\GlobalObjectDeprecationDecorator('TYPO3\\CMS\\Core\\Cache\\CacheFactory');
            }
        } else {
            $cacheManager = new \TYPO3\CMS\Core\Cache\CacheManager();
            $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
            \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Cache\CacheManager::class, $cacheManager);

            $cacheFactory = new \TYPO3\CMS\Core\Cache\CacheFactory('production', $cacheManager);
            \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Cache\CacheFactory::class, $cacheFactory);

            $bootstrap->setEarlyInstance(\TYPO3\CMS\Core\Cache\CacheManager::class, $cacheManager);
        }
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
    public static function initializeClassLoaderCaches(ConsoleBootstrap $bootstrap)
    {
        if (is_callable(array($bootstrap, 'initializeClassLoaderCaches'))) {
            $bootstrap->initializeClassLoaderCaches();
            $packageStatesPathAndFilename = PATH_typo3conf . 'PackageStates.php';
            $mTime = @filemtime($packageStatesPathAndFilename);
            $bootstrap->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader')
                ->setCacheIdentifier(md5($packageStatesPathAndFilename . $mTime))
                ->setPackages($bootstrap->getEarlyInstance('TYPO3\\Flow\\Package\\PackageManager')->getActivePackages());
        }
    }

    /**
     * @param ConsoleBootstrap $bootstrap
     */
    public static function initializeExtensionConfiguration(ConsoleBootstrap $bootstrap)
    {
        // Manual load GlobalDebugFunctions.php for TYPO3 CMS <= 7.3
        if (file_exists(PATH_site . 'typo3/sysext/core/Classes/Core/GlobalDebugFunctions.php')) {
            require_once PATH_site . 'typo3/sysext/core/Classes/Core/GlobalDebugFunctions.php';
        }
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
        // TODO: avoid throwing a deprecation message with this call
        /** @var $backendUser \TYPO3\CMS\Core\Authentication\BackendUserAuthentication */
        $backendUser = $GLOBALS['BE_USER'];
        if (is_callable(array($backendUser, 'checkCLIuser'))) {
            $backendUser->checkCLIuser();
        } else {
            self::loadCommandLineBackendUser('_CLI_lowlevel');
        }
        $backendUser->backendCheckLogin();
        if (method_exists($bootstrap, 'initializeBackendUserMounts')) {
            $bootstrap->initializeBackendUserMounts();
        }
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
     * Provide cleaned imlementation of TYPO3 CMS core classes.
     * Can only be called *after* extension configuration is loaded (needs extbase configuration)!
     *
     * @param ConsoleBootstrap $bootstrap
     */
    public static function provideCleanClassImplementations(ConsoleBootstrap $bootstrap)
    {
        self::overrideImplementation('TYPO3\CMS\Extbase\Mvc\Controller\Argument', 'Helhum\Typo3Console\Mvc\Controller\Argument');
    }

    /**
     * Tell Extbase, TYPO3 and PHP that we have another implementation
     */
    public static function overrideImplementation($originalClassName, $overrideClassName)
    {
        /** @var $extbaseObjectContainer \TYPO3\CMS\Extbase\Object\Container\Container */
        $extbaseObjectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\Container\\Container');
        $extbaseObjectContainer->registerImplementation($originalClassName, $overrideClassName);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][$originalClassName]['className'] = $overrideClassName;
        class_alias($overrideClassName, $originalClassName);
    }
}
