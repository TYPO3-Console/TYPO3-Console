<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Package;

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

use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;

class UncachedPackageManager extends PackageManager
{
    /**
     * @var bool
     */
    protected $forceSavePackageStates = false;

    /**
     * @var bool
     */
    protected $packageStatesFileExists = false;

    /**
     * @param DependencyOrderingService $dependencyOrderingService
     */
    public function injectDependencyOrderingService(DependencyOrderingService $dependencyOrderingService)
    {
        $this->dependencyOrderingService = $dependencyOrderingService;
    }

    public function init()
    {
        $this->packageStatesFileExists = @file_exists($this->packageStatesPathAndFilename);
        $this->loadPackageStates();
        $this->initializePackageObjects();
        $this->initializeCompatibilityLoadedExtArray();
    }

    protected function loadPackageStates()
    {
        if ($this->packageStatesFileExists) {
            parent::loadPackageStates();
        } else {
            $this->scanAvailablePackages();
        }
    }

    /**
     * Get the extension configuration as array from the config file
     *
     * @param PackageInterface $package
     * @return array
     */
    public function getExtensionConfiguration(PackageInterface $package)
    {
        return parent::getExtensionEmConf($package->getPackagePath());
    }

    /**
     * Only save a new PackageSates file if there is only one,
     * to prevent saving one before TYPO3 is properly installed
     */
    protected function sortAndSavePackageStates()
    {
        if ($this->packageStatesFileExists) {
            parent::sortAndSavePackageStates();
        }
    }

    /**
     * Overload original method because the stupid TYPO3 core
     * tries to sort packages by dependencies before *DEACTIVATING* a package
     * In this case we do nothing now until this TYPO3 bug is fixed.
     * @return array
     */
    protected function sortActivePackagesByDependencies()
    {
        if (!$this->forceSavePackageStates) {
            return [];
        }

        return parent::sortActivePackagesByDependencies();
    }

    /**
     * To enable writing of the package states file the package states
     * migration needs to override eventual failsafe blocks.
     * This will be used during installation process.
     */
    public function forceSortAndSavePackageStates()
    {
        $this->forceSavePackageStates = true;
        parent::sortAndSavePackageStates();
        $this->forceSavePackageStates = false;
    }
}
