<?php
namespace Helhum\Typo3Console\Install;

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
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This class generates the PackageStates.php file from composer.json configuration
 */
class PackageStatesGenerator
{
    /**
     * @var UncachedPackageManager
     */
    private $packageManager;

    /**
     * PackageStatesGenerator constructor.
     *
     * @param UncachedPackageManager $packageManager
     */
    public function __construct(UncachedPackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    /**
     * @param array $frameworkExtensionsToActivate
     * @param bool $activateDefaultExtensions
     * @param array $excludedExtensions
     * @return PackageInterface[]
     */
    public function generate(array $frameworkExtensionsToActivate = [], $activateDefaultExtensions = false, array $excludedExtensions = [])
    {
        $this->ensureDirectoryExists(PATH_site . 'typo3conf');
        $this->packageManager->scanAvailablePackages();
        foreach ($this->packageManager->getAvailablePackages() as $package) {
            if (
                in_array($package->getPackageKey(), $frameworkExtensionsToActivate, true)
                || $package->isProtected()
                || $package->isPartOfMinimalUsableSystem()
                || ($activateDefaultExtensions && $package->isPartOfFactoryDefault())
                // Every extension available in typo3conf/ext is meant to be active
                // except it is added to the exclude array. The latter is useful in dev mode or non composer mode
                || (
                    strpos(PathUtility::stripPathSitePrefix($package->getPackagePath()), 'typo3conf/ext/') !== false
                    && !in_array($package->getPackageKey(), $excludedExtensions, true)
                    )
            ) {
                $this->packageManager->activatePackage($package->getPackageKey());
            } else {
                $this->packageManager->deactivatePackage($package->getPackageKey());
            }
        }
        $this->packageManager->forceSortAndSavePackageStates();
        return $this->packageManager->getActivePackages();
    }

    /**
     * @param string $directory
     */
    private function ensureDirectoryExists($directory)
    {
        if (!is_dir($directory)) {
            GeneralUtility::mkdir_deep(rtrim($directory, '/\\') . '/');
        }
    }
}
