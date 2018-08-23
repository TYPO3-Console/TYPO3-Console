<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\TYPO3v91\Core\Booting;

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

use Helhum\Typo3Console\Package\UncachedPackageManager;
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

    public static function initializeConfigurationManagement()
    {
        // noop for TYPO3 9
    }

    /**
     * @deprecated can be removed when TYPO3 8 support is removed
     */
    public static function initializeDatabaseConnection()
    {
        // noop for TYPO3 9
    }

    public static function initializeExtensionConfiguration()
    {
        // noop for TYPO3 9
    }
}
