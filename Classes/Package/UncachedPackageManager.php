<?php
namespace Helhum\Typo3Console\Package;

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

use Helhum\Typo3Console\Core\ConsoleBootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
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
     * @var bool
     */
    protected $packageStatesFileExists = false;

    /**
     * @var bool
     */
    protected $hasExtension = false;

    public function init()
    {
        $this->packageStatesFileExists = @file_exists($this->packageStatesPathAndFilename);
        $this->hasExtension = @file_exists(PATH_site . 'typo3conf/ext/typo3_console/ext_emconf.php');
        $this->loadPackageStates();
        $this->makeConsolePackageProtectedIfNeeded();
        $this->initializePackageObjects();
        $this->autoActivateConsolePackageIfPossible();
        $this->registerConsoleClassesIfNeeded();
        $this->initializeCompatibilityLoadedExtArray();
    }

    protected function loadPackageStates()
    {
        $this->packageStatesConfiguration = $this->packageStatesFileExists ? include($this->packageStatesPathAndFilename) : array();
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
        if ($this->packageStatesFileExists) {
            parent::sortAndSavePackageStates();
            $this->packageStatesFileExists = true;
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
        // Force loading of the console in case no package states file is there
        if ($this->hasExtension && !$this->packageStatesFileExists) {
            $this->getPackage('typo3_console')->setProtected(true);
        }
    }

    protected function autoActivateConsolePackageIfPossible()
    {
        if ($this->hasExtension && $this->packageStatesFileExists && !$this->isPackageActive('typo3_console')) {
            $this->scanAvailablePackages();
            $this->activatePackage('typo3_console');
            if (!ConsoleBootstrap::usesComposerClassLoading()) {
                ClassLoadingInformation::dumpClassLoadingInformation();
            }
        }
    }

    protected function registerConsoleClassesIfNeeded()
    {
        if ($this->hasExtension && !class_exists(\Helhum\Typo3Console\Core\Booting\RunLevel::class)) {
            // Since the class loader now assumes that this class does not exist, we require it manually here
            require __DIR__ . '/../Core/Booting/RunLevel.php';
            ClassLoadingInformation::registerTransientClassLoadingInformationForPackage($this->getPackage('typo3_console'));
        }
    }
}
