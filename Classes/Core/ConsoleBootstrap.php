<?php
namespace Helhum\Typo3Console\Core;

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

use Helhum\Typo3Console\Core\Booting\RunLevel;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * @internal
 */
class ConsoleBootstrap extends Bootstrap
{
    /**
     * @param string $runLevel
     * @deprecated Will be removed with 5.0
     */
    public function requestRunLevel($runLevel)
    {
        $sequence = $this->getEarlyInstance(RunLevel::class)->buildSequence($runLevel);
        $sequence->invoke($this);
    }

    public function disableCoreCaches()
    {
        $this->disableCoreCache();
        // @deprecated can be removed once TYPO3 7 support is removed
        /** @var PackageManager $packageManager */
        $packageManager = $this->getEarlyInstance(\TYPO3\CMS\Core\Package\PackageManager::class);
        if ($packageManager->isPackageActive('dbal')) {
            $cacheConfigurations = &$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];
            $cacheConfigurations['dbal'] = [
                'backend' => \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class,
                'groups' => [],
            ];
        }
    }

    public function initializeConfigurationManagement()
    {
        $this->populateLocalConfiguration();
        if (!self::usesComposerClassLoading()) {
            $this->initializeRuntimeActivatedPackagesFromConfiguration();
        }
        // Because links might be generated from CLI (e.g. by Solr indexer)
        // We need to properly initialize the cache hash calculator here!
        // @deprecated can be removed if TYPO3 8 support is removed
        if (is_callable([$this, 'setCacheHashOptions'])) {
            $this->setCacheHashOptions();
        }
        $this->setDefaultTimezone();
        // @deprecated can be removed if TYPO3 8 support is removed
        if (is_callable([$this, 'defineUserAgentConstant'])) {
            $this->defineUserAgentConstant();
        }
        // @deprecated can be removed if TYPO3 7 support is removed
        if (is_callable([$this, 'defineDatabaseConstants'])) {
            $this->defineDatabaseConstants();
        }
    }

    /**
     * @deprecated can be removed if TYPO3 7 support is removed (directly use $bootstrap->loadBaseTca())
     */
    public function loadTcaOnly()
    {
        ExtensionManagementUtility::loadBaseTca();
    }

    /**
     * @deprecated can be removed if TYPO3 7 support is removed (directly use $bootstrap->loadExtTables())
     */
    public function loadExtTablesOnly()
    {
        ExtensionManagementUtility::loadExtTables();
        if (is_callable([$this, 'executeExtTablesAdditionalFile'])) {
            $this->executeExtTablesAdditionalFile();
        }
        $this->runExtTablesPostProcessingHooks();
    }

    /**
     * @deprecated can be removed if TYPO3 8 support is removed
     */
    public function initializeDatabaseConnection()
    {
        if (is_callable([$this, 'initializeTypo3DbGlobal'])) {
            $this->initializeTypo3DbGlobal();
        }
    }

    /**
     * Sets up additional configuration applied in all scopes
     *
     * @return Bootstrap
     * @internal This is not a public API method, do not use in own extensions
     */
    public function applyAdditionalConfigurationSettings()
    {
        $this->setFinalCachingFrameworkCacheConfiguration();
        // @deprecated can be removed once TYPO3 8.7 support is removed
        if (is_callable([$this, 'defineLoggingAndExceptionConstants'])) {
            $this->defineLoggingAndExceptionConstants();
        }
        $this->unsetReservedGlobalVariables();
        return $this;
    }
}
