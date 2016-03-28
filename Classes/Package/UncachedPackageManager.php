<?php
namespace Helhum\Typo3Console\Package;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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

    /**
     * @param Bootstrap $bootstrap
     */
    public function init(Bootstrap $bootstrap)
    {
        $this->loadPackageStates();
        $this->initializePackageObjects();
        $this->initializeCompatibilityLoadedExtArray();

        $this->getPackage('typo3_console')->bootPackage($bootstrap);
    }

    /**
     * Intended to be called by the cache warmup only
     * @internal
     */
    public function populatePackageCache()
    {
        $this->saveToPackageCache();
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
            $this->registerPackagesFromConfiguration();
        }

        if ($this->consolePackageBootRequired($this->getPackage('typo3_console'))) {
            $this->packages['typo3_console'] = new \Helhum\Typo3Console\Package($this, 'typo3_console', $this->getPackage('typo3_console')->getPackagePath());
        }
    }

    /**
     * @param PackageInterface $consolePackage
     * @return bool
     */
    protected function consolePackageBootRequired($consolePackage)
    {
        return !$consolePackage instanceof \Helhum\Typo3Console\Package;
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
}
