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
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Page\PageRenderer;

class Scripts
{
    public static function initializeErrorHandling(): void
    {
        error_reporting(E_ALL & ~E_NOTICE);
        $exceptionHandler = new ExceptionHandler();
        set_exception_handler([$exceptionHandler, 'handleException']);

        $enforcedExceptionalErrors = E_WARNING | E_USER_WARNING | E_USER_ERROR | E_RECOVERABLE_ERROR;
        $errorHandlerErrors = $GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandlerErrors'] ?? E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR);
        // Ensure all exceptional errors are handled including E_USER_NOTICE and E_USER_DEPRECATED
        $errorHandlerErrors = $errorHandlerErrors | E_USER_NOTICE | E_USER_DEPRECATED | $enforcedExceptionalErrors;
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

    public static function initializeExtensionConfiguration(ContainerInterface $container): void
    {
        if ($container instanceof Container && $container->get('boot.state')->runLevel === RunLevel::LEVEL_FULL) {
            // Fetch a "cached" instance of the container
            $bootService = $container->get(BootService::class);
            $container = $bootService->getContainer();
            $bootService->makeCurrent($container);
        }
        $container->get('boot.state')->done = false;
        $assetsCache = $container->get('cache.assets');
        $coreCache = $container->get('cache.core');
        PageRenderer::setCache($assetsCache);
        Bootstrap::loadTypo3LoadedExtAndExtLocalconf(true, $coreCache);
        Bootstrap::unsetReservedGlobalVariables();
        $container->get('boot.state')->done = true;
    }

    public static function initializePersistence(ContainerInterface $container): void
    {
        Bootstrap::loadBaseTca(true, $container->get('cache.core'));
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            throw new \RuntimeException(
                'TYPO3 Encryption is empty. $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'encryptionKey\'] needs to be set for TYPO3 to work securely',
                1502987245
            );
        }
    }

    public static function initializeAuthenticatedOperations(): void
    {
        Bootstrap::loadExtTables();
        Bootstrap::initializeBackendUser(CommandLineUserAuthentication::class);
        Bootstrap::initializeBackendAuthentication();
        Bootstrap::initializeLanguageObject();
    }
}
