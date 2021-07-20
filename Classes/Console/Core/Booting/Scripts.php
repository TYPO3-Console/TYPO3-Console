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
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Imaging\IconRegistry;
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
            self::enableEarlyCachesInContainer($container);
        }
        $container->get('boot.state')->done = false;
        $assetsCache = $container->get('cache.assets');
        $coreCache = $container->get('cache.core');
        // compatibility to < v11
        if (method_exists(IconRegistry::class, 'setCache')) {
            IconRegistry::setCache($assetsCache);
        }
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
        // Here until this is fixed https://forge.typo3.org/issues/94592
        $GLOBALS['BE_USER'] = new class() extends CommandLineUserAuthentication {
            public function authenticate()
            {
                // check if a _CLI_ user exists, if not, create one
                $this->setBeUserByName($this->username);
                if (empty($this->user['uid'])) {
                    // create a new BE user in the database
                    if (!$this->checkIfCliUserExists()) {
                        $this->createCliUser();
                    } else {
                        throw new \RuntimeException('No backend user named "_cli_" could be authenticated, maybe this user is "hidden"?', 1484050401);
                    }
                    $this->setBeUserByName($this->username);
                }
                if (empty($this->user['uid'])) {
                    throw new \RuntimeException('No backend user named "_cli_" could be created.', 1476107195);
                }
                // The groups are fetched and ready for permission checking in this initialization.
                $this->fetchGroupData();
                $this->backendSetUC();
            }
        };
        $GLOBALS['BE_USER']->setLogger(new NullLogger());
        $GLOBALS['BE_USER']->start();
//        Bootstrap::initializeBackendUser(CommandLineUserAuthentication::class);
        Bootstrap::initializeBackendAuthentication();
        Bootstrap::initializeLanguageObject();
    }

    /**
     * We simulate caches in full boot mode, although we booted failsafe
     *
     * @param Container $container
     * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidBackendException
     * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidCacheException
     */
    private static function enableEarlyCachesInContainer(Container $container): void
    {
        $container->get('boot.state')->cacheDisabled = false;
        $container->set('cache.core', Bootstrap::createCache('core'));
        $container->set('cache.assets', Bootstrap::createCache('assets'));
    }
}
