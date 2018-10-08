<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\TYPO3v87\Core\Booting;

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

use Doctrine\Common\Annotations\AnnotationReader;
use Helhum\Typo3Console\Package\UncachedPackageManager;
use Helhum\Typo3Console\TYPO3v87\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\DependencyResolver;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CompatibilityScripts
{
    public static function isComposerMode(): bool
    {
        return Bootstrap::usesComposerClassLoading();
    }

    public static function createCacheManager(bool $disableCaching): CacheManager
    {
        $cacheManager = new CacheManager($disableCaching);
        $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);

        return $cacheManager;
    }

    public static function createPackageManager(): UncachedPackageManager
    {
        $packageManager = new UncachedPackageManager();
        $dependencyResolver = GeneralUtility::makeInstance(DependencyResolver::class);
        $dependencyResolver->injectDependencyOrderingService(
            GeneralUtility::makeInstance(DependencyOrderingService::class)
        );
        $packageManager->injectDependencyResolver($dependencyResolver);

        return $packageManager;
    }

    public static function initializeConfigurationManagement(Bootstrap $bootstrap)
    {
        self::initializeAnnotationReader();

        \Closure::bind(function () use ($bootstrap) {
            // Because links might be generated from CLI (e.g. by Solr indexer)
            // We need to properly initialize the cache hash calculator here!
            $method = 'setCacheHashOptions';
            $bootstrap->$method();
            $method = 'defineUserAgentConstant';
            $bootstrap->$method();
        }, null, $bootstrap)();
    }

    private static function initializeAnnotationReader()
    {
        /*
         * All annotations defined by and for Extbase need to be
         * ignored during their deprecation. Later, their usage may and
         * should throw an Exception
         */
        AnnotationReader::addGlobalIgnoredName('inject');
        AnnotationReader::addGlobalIgnoredName('transient');
        AnnotationReader::addGlobalIgnoredName('lazy');
        AnnotationReader::addGlobalIgnoredName('validate');
        AnnotationReader::addGlobalIgnoredName('cascade');
        AnnotationReader::addGlobalIgnoredName('ignorevalidation');
        AnnotationReader::addGlobalIgnoredName('cli');
        AnnotationReader::addGlobalIgnoredName('flushesCaches');
        AnnotationReader::addGlobalIgnoredName('uuid');
        AnnotationReader::addGlobalIgnoredName('identity');

        // Annotations used in unit tests
        AnnotationReader::addGlobalIgnoredName('test');

        // Annotations that control the extension scanner
        AnnotationReader::addGlobalIgnoredName('extensionScannerIgnoreFile');
        AnnotationReader::addGlobalIgnoredName('extensionScannerIgnoreLine');
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
}
