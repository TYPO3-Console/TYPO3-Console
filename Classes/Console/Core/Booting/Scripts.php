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
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Scripts
{
    public static function initializeErrorHandling()
    {
        error_reporting(E_ALL & ~E_NOTICE);
        $exceptionHandler = new ExceptionHandler();
        set_exception_handler([$exceptionHandler, 'handleException']);

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

    public static function initializeDisabledCaching()
    {
        self::initializeCachingFramework(true);
    }

    public static function initializeCaching()
    {
        self::initializeCachingFramework();
    }

    private static function initializeCachingFramework(bool $disableCaching = false)
    {
        $cacheManager = CompatibilityScripts::createCacheManager($disableCaching);
        \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager);
    }

    public static function initializeExtensionConfiguration()
    {
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $assetsCache = $cacheManager->getCache('assets');
        $coreCache = $cacheManager->getCache('cache_core');
        IconRegistry::setCache($assetsCache);
        // TODO: only in v10
//        PageRenderer::setCache($assetsCache);
        Bootstrap::loadTypo3LoadedExtAndExtLocalconf(true, $coreCache);
        if (is_callable([Bootstrap::class, 'setFinalCachingFrameworkCacheConfiguration'])) {
            Bootstrap::setFinalCachingFrameworkCacheConfiguration($cacheManager);
        }
        Bootstrap::unsetReservedGlobalVariables();
    }

    public static function initializePersistence()
    {
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $coreCache = $cacheManager->getCache('cache_core');
        Bootstrap::loadBaseTca(true, $coreCache);
        \Closure::bind(function () {
            Bootstrap::checkEncryptionKey();
        }, null, Bootstrap::class)();
    }

    public static function initializeAuthenticatedOperations()
    {
        Bootstrap::loadExtTables();
        Bootstrap::initializeBackendUser(CommandLineUserAuthentication::class);
        Bootstrap::initializeBackendAuthentication();
    }
}
