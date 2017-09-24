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

use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;

class CompatibilityScripts
{
    /**
     * @param Bootstrap $bootstrap
     */
    public static function initializeConfigurationManagement(Bootstrap $bootstrap)
    {
        \Closure::bind(function () use ($bootstrap) {
            // Because links might be generated from CLI (e.g. by Solr indexer)
            // We need to properly initialize the cache hash calculator here!
            $bootstrap->setCacheHashOptions();
            $bootstrap->defineUserAgentConstant();
        }, null, $bootstrap)();
    }

    /**
     * @param Bootstrap $bootstrap
     */
    public static function initializeCachingFramework(Bootstrap $bootstrap)
    {
        // noop for TYPO3 8
    }

    /**
     * @param Bootstrap $bootstrap
     */
    public static function initializeDatabaseConnection(Bootstrap $bootstrap)
    {
        $bootstrap->initializeTypo3DbGlobal();
    }

    /**
     * @param Bootstrap $bootstrap
     */
    public static function initializeExtensionConfiguration(Bootstrap $bootstrap)
    {
        $bootstrap->defineLoggingAndExceptionConstants();
    }

    /**
     * @param Bootstrap $bootstrap
     */
    public static function initializeAuthenticatedOperations(Bootstrap $bootstrap)
    {
        $bootstrap->loadExtTables();
        $bootstrap->initializeBackendUser(CommandLineUserAuthentication::class);
        self::loadCommandLineBackendUser();
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
}
