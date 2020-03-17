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

use TYPO3\CMS\Core\Package\FailsafePackageManager;

class UncachedPackageManager extends FailsafePackageManager
{
    /**
     * @var bool
     */
    private $forceSavePackageStates = false;

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
