<?php
namespace Helhum\Typo3Console\Package;

/*
 * This file is part of the typo3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Class UncachedPackageManager
 */
class UncachedPackageManager extends PackageManager
{
    /**
     * @var bool
     */
    protected $forceSavePackageStates = false;

    public function init()
    {
        $this->loadPackageStates();
        $this->makeConsolePackageProtectedIfNeeded();
        $this->initializePackageObjects();
        $this->initializeCompatibilityLoadedExtArray();
    }

    protected function loadPackageStates()
    {
        $this->packageStatesConfiguration = file_exists($this->packageStatesPathAndFilename) ? include($this->packageStatesPathAndFilename) : array();
        if (!isset($this->packageStatesConfiguration['version']) || $this->packageStatesConfiguration['version'] < 4) {
            $this->packageStatesConfiguration = array();
        }
        if ($this->packageStatesConfiguration === array()) {
            $this->scanAvailablePackages();
        } else {
            $this->registerPackagesFromConfiguration($this->packageStatesConfiguration['packages']);
        }
    }

    /**
     * Only save a new PackageSates file if there is only one,
     * to prevent saving one before TYPO3 is properly installed
     */
    protected function sortAndSavePackageStates()
    {
        if (@file_exists($this->packageStatesPathAndFilename)) {
            parent::sortAndSavePackageStates();
        }
    }

    /**
     * Overload original method because the stupid TYPO3 core
     * tries to sort packages by dependencies before *DEACTIVATING* a package
     * In this case we do nothing now until this TYPO3 bug is fixed.
     */
    protected function sortActivePackagesByDependencies()
    {
        if (!$this->forceSavePackageStates) {
            return array();
        }
        return parent::sortActivePackagesByDependencies();
    }

    /**
     * Overload original method because the stupid TYPO3 core
     * tries to sort packages by dependencies before *DEACTIVATING* a package
     * In this case we do nothing now until this TYPO3 bug is fixed.
     *
     * @deprecated in 8.0 will be removed once 7.6 compatibility is removed
     */
    protected function sortAvailablePackagesByDependencies()
    {
        if ($this->forceSavePackageStates) {
            parent::sortAvailablePackagesByDependencies();
        }
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

    protected function makeConsolePackageProtectedIfNeeded()
    {
        if (!file_exists(PATH_site . 'typo3conf/PackageStates.php')) {
            // Force loading of the console in case we do not have a package states file yet (pre-install)
            $this->getPackage('typo3_console')->setProtected(true);
        }
    }
}
