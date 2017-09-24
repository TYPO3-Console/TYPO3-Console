<?php
namespace Helhum\Typo3Console\LTS7\Core\Booting;

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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
            $bootstrap->defineDatabaseConstants();
        }, null, $bootstrap)();
    }

    /**
     * @param Bootstrap $bootstrap
     */
    public static function initializeCachingFramework(Bootstrap $bootstrap)
    {
        $cacheFactory = new \TYPO3\CMS\Core\Cache\CacheFactory('production', $bootstrap->getEarlyInstance(\TYPO3\CMS\Core\Cache\CacheManager::class));
        \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Cache\CacheFactory::class, $cacheFactory);
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
        ExtensionManagementUtility::loadExtTables();
        \Closure::bind(function () use ($bootstrap) {
            $bootstrap->executeExtTablesAdditionalFile();
            $bootstrap->runExtTablesPostProcessingHooks();
        }, null, $bootstrap)();

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
        if (is_callable([$backendUser, 'authenticate'])) {
            $backendUser->authenticate();
        } else {
            // @deprecated can be removed once TYPO3 7.6 support is removed
            $userName = '_cli_lowlevel';
            $backendUser->setBeUserByName($userName);
            if (!$backendUser->user['uid']) {
                /** @var DatabaseConnection $db */
                $db = $GLOBALS['TYPO3_DB'];
                $db->exec_INSERTquery(
                    'be_users',
                    [
                        'username' => $userName,
                        'password' => GeneralUtility::getRandomHexString(48),
                    ]
                );
                $backendUser->setBeUserByName($userName);
            }
            if (!$backendUser->user['uid']) {
                throw new \RuntimeException('No backend user named "' . $userName . '" was found or could not be created! Please create it manually!', 3);
            }
            if ($backendUser->isAdmin()) {
                throw new \RuntimeException('CLI backend user "' . $userName . '" was ADMIN which is not allowed!', 3);
            }
            $backendUser->backendCheckLogin();
        }
    }
}
