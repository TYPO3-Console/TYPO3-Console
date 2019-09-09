<?php
declare(strict_types=1);
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

use Helhum\Typo3Console\Error\ErrorHandler;
use Helhum\Typo3Console\Error\ExceptionHandler;
use Helhum\Typo3Console\Property\TypeConverter\ArrayConverter;
use Symfony\Component\Console\Exception\RuntimeException;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Property\TypeConverter\BooleanConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\FloatConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\IntegerConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\StringConverter;

class Scripts
{
    /**
     * @param Bootstrap $bootstrap
     */
    public static function initializeConfigurationManagement(Bootstrap $bootstrap)
    {
        $bootstrap->populateLocalConfiguration();
        \Closure::bind(function () use ($bootstrap) {
            $method = 'initializeRuntimeActivatedPackagesFromConfiguration';
            if (!CompatibilityScripts::isComposerMode()) {
                $bootstrap->$method(GeneralUtility::makeInstance(PackageManager::class));
            }
            $method = 'setDefaultTimezone';
            $bootstrap->$method();
        }, null, $bootstrap)();
        CompatibilityScripts::initializeConfigurationManagement($bootstrap);
    }

    public static function baseSetup(Bootstrap $bootstrap)
    {
        define('TYPO3_MODE', 'BE');
        define('PATH_site', \TYPO3\CMS\Core\Utility\GeneralUtility::fixWindowsFilePath(getenv('TYPO3_PATH_ROOT')) . '/');
        define('PATH_thisScript', PATH_site . 'typo3/index.php');

        $bootstrap->setRequestType(TYPO3_REQUESTTYPE_BE | TYPO3_REQUESTTYPE_CLI);
        $bootstrap->baseSetup();
        // Mute notices
        error_reporting(E_ALL & ~E_NOTICE);
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
        $packageManager = CompatibilityScripts::createPackageManager();
        $bootstrap->setEarlyInstance(PackageManager::class, $packageManager);
        GeneralUtility::setSingletonInstance(PackageManager::class, $packageManager);
        ExtensionManagementUtility::setPackageManager($packageManager);
        $packageManager->init();
    }

    public static function initializeErrorHandling()
    {
        $enforcedExceptionalErrors = E_WARNING | E_USER_WARNING | E_USER_ERROR | E_RECOVERABLE_ERROR;
        $errorHandlerErrors = $GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandlerErrors'] ?? E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR);
        // Ensure all exceptional errors are handled including E_USER_NOTICE
        $errorHandlerErrors = $errorHandlerErrors | E_USER_NOTICE | $enforcedExceptionalErrors;
        // Ensure notices are excluded to avoid overhead in the error handler
        $errorHandlerErrors &= ~E_NOTICE;
        $errorHandler = new ErrorHandler();
        $errorHandler->setErrorsToHandle($errorHandlerErrors);
        $exceptionalErrors = $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'] ?? E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR | E_DEPRECATED | E_USER_DEPRECATED | E_USER_NOTICE);
        // Ensure warnings and errors are turned into exceptions
        $exceptionalErrors = ($exceptionalErrors | $enforcedExceptionalErrors) & ~E_USER_DEPRECATED;
        $errorHandler->setExceptionalErrors($exceptionalErrors);
        set_error_handler([$errorHandler, 'handleError']);
    }

    public static function initializeDisabledCaching(Bootstrap $bootstrap)
    {
        self::initializeCachingFramework($bootstrap, true);
    }

    public static function initializeCaching(Bootstrap $bootstrap)
    {
        self::initializeCachingFramework($bootstrap);
    }

    private static function initializeCachingFramework(Bootstrap $bootstrap, bool $disableCaching = false)
    {
        $cacheManager = CompatibilityScripts::createCacheManager($disableCaching);
        \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager);
        $bootstrap->setEarlyInstance(CacheManager::class, $cacheManager);
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
    }

    public static function initializePersistence()
    {
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
     * @throws RuntimeException if a non-admin Backend user could not be loaded
     */
    private static function loadCommandLineBackendUser()
    {
        /** @var CommandLineUserAuthentication $backendUser */
        $backendUser = $GLOBALS['BE_USER'];
        if ($backendUser->user['uid']) {
            throw new RuntimeException('Another user was already loaded which is impossible in CLI mode!', 3);
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

        // @deprecated can be removed once command controller support is removed
        if (empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'] = [];
        }
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'][] = ArrayConverter::class;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'][] = StringConverter::class;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'][] = BooleanConverter::class;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'][] = IntegerConverter::class;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'][] = FloatConverter::class;
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
