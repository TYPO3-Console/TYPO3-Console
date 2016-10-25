<?php
namespace Helhum\Typo3Console\Install;

/*
 * This file is part of the TYPO3 console project.
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
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This class generates the PackageStates.php file from composer.json configuration
 */
class PackageStatesGenerator
{
    /**
     * @param UncachedPackageManager $packageManager
     * @param bool $activateDefaultExtensions
     * @throws \TYPO3\CMS\Core\Package\Exception\ProtectedPackageKeyException
     */
    public function generate(UncachedPackageManager $packageManager, $activateDefaultExtensions = false)
    {
        $frameworkExtensionsFromConfiguration = $this->getFrameworkExtensionsFromConfiguration();
        $packageManager->scanAvailablePackages();
        foreach ($packageManager->getAvailablePackages() as $package) {
            if (
                isset($frameworkExtensionsFromConfiguration[$package->getPackageKey()])
                || $package->isProtected()
                || $package->isPartOfMinimalUsableSystem()
                || ($activateDefaultExtensions && $package->isPartOfFactoryDefault())
                // Every extension available in typo3conf/ext is meant to be active
                || strpos(PathUtility::stripPathSitePrefix($package->getPackagePath()), 'typo3conf/ext/') !== false
            ) {
                $packageManager->activatePackage($package->getPackageKey());
            } else {
                $packageManager->deactivatePackage($package->getPackageKey());
            }
        }
        $packageManager->forceSortAndSavePackageStates();
    }

    /**
     * @return array
     */
    public function getFrameworkExtensionsFromConfiguration()
    {
        $configuredExtensions = [];
        if (getenv('TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS')) {
            $configuredExtensions = array_flip(explode(',', getenv('TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS')));
        }
        return $configuredExtensions;
    }
}
