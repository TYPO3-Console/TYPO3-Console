<?php
declare(strict_types=1);
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
     * @param array $excludedExtensions
     * @param bool $activateDefaultExtensions
     * @return PackageInterface[]
     */
    public function generate(array $frameworkExtensionsToActivate = [], array $excludedExtensions = [], $activateDefaultExtensions = false)
    {
        $this->ensureDirectoryExists(PATH_site . 'typo3conf');
        $this->packageManager->scanAvailablePackages();
        foreach ($this->packageManager->getAvailablePackages() as $package) {
            $extKey = $package->getPackageKey();
            $isLocalExt = strpos(PathUtility::stripPathSitePrefix($package->getPackagePath()), 'typo3conf/ext/') !== false;
            $isFrameWorkExtToActivate = in_array($extKey, $frameworkExtensionsToActivate, true);
            $isExcludedExt = in_array($extKey, $excludedExtensions, true);
            $isFactoryDefault = $activateDefaultExtensions && $package->isPartOfFactoryDefault();
            $isRequiredFrameworkExt = $isFrameWorkExtToActivate || $package->isProtected() || $package->isPartOfMinimalUsableSystem();
            if (($isRequiredFrameworkExt || $isLocalExt || $isFactoryDefault) && (!$isExcludedExt || $isRequiredFrameworkExt)) {
                $this->packageManager->activatePackage($extKey);
            } else {
                $this->packageManager->deactivatePackage($extKey);
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
