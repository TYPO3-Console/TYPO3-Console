<?php
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

use Helhum\Typo3Console\Core\ConsoleBootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;

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
        $this->makeConsolePackageProtected();
        $this->initializePackageObjects();
        $this->ensureClassLoadingInformationExists();
        $this->autoActivateConsolePackage();
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

    /**
     * Workaround for non Composer mode
     *
     * Force loading of the console in case no package states file is there
     * This is needed for installation or package states file generation commands
     */
    protected function makeConsolePackageProtected()
    {
        if ($this->hasExtension && !$this->packageStatesFileExists) {
            $this->getPackage('typo3_console')->setProtected(true);
        }
    }

    /**
     * Workaround for non Composer mode
     *
     * @deprecated since 8.0 will be removed once 7.6 compatiblity is removed
     */
    protected function ensureClassLoadingInformationExists()
    {
        if (!ConsoleBootstrap::usesComposerClassLoading() && !ClassLoadingInformation::isClassLoadingInformationAvailable()) {
            ClassLoadingInformation::dumpClassLoadingInformation();
            ClassLoadingInformation::registerClassLoadingInformation();
        }
    }

    /**
     * Workaround for non Composer mode
     *
     * Make sure the extension is active
     */
    protected function autoActivateConsolePackage()
    {
        if ($this->hasExtension && $this->packageStatesFileExists && !$this->isPackageActive('typo3_console')) {
            $this->scanAvailablePackages();
            $this->activatePackage('typo3_console');
            if (!ConsoleBootstrap::usesComposerClassLoading()) {
                // Activate Package does not permanently update autoload info
                // thus we must do so here manually
                ClassLoadingInformation::dumpClassLoadingInformation();
            }
        }
    }
}
