<?php
namespace Helhum\Typo3Console\Command;

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

use Helhum\Typo3Console\Mvc\Controller\CommandController;

/**
 * Class BackendCommandController
 */
class BackendCommandController extends CommandController
{
    const LOCK_TYPE_UNLOCKED = 0;
    const LOCK_TYPE_ADMIN = 2;

    /**
     * @var \Helhum\Typo3Console\Service\Configuration\ConfigurationService
     * @inject
     */
    protected $configurationService;

    /**
     * Locks backend access for all users by writing a lock file that is checked when the backend is accessed.
     *
     * @param string $redirectUrl URL to redirect to when the backend is accessed
     * @param bool $adminOnly Locked only for admins
     */
    public function lockCommand($redirectUrl = null, $adminOnly = false)
    {
        if (!$adminOnly) {
            if (@is_file((PATH_typo3conf . 'LOCK_BACKEND'))) {
                $this->outputLine('A lockfile already exists. Overwriting it...');
            }

            \TYPO3\CMS\Core\Utility\GeneralUtility::writeFile(PATH_typo3conf . 'LOCK_BACKEND', (string)$redirectUrl);

            if ($redirectUrl === null) {
                $this->outputLine('Wrote lock file to \'typo3conf/LOCK_BACKEND\'');
            } else {
                $this->outputLine('Wrote lock file to \'typo3conf/LOCK_BACKEND\' with instruction to redirect to: \'' . $redirectUrl . '\'');
            }
        } elseif ($this->configurationService->localIsActive('BE/adminOnly')) {
            $this->configurationService->setLocal('BE/adminOnly', self::LOCK_TYPE_ADMIN);
            $this->outputLine('Locked backend for admin only access!');
        }
    }

    /**
     * Unlocks the backend access by deleting the lock file
     */
    public function unlockCommand()
    {
        $lockedForAdmins = $this->configurationService->localIsActive('BE/adminOnly') && $this->configurationService->getLocal('BE/adminOnly') !== self::LOCK_TYPE_UNLOCKED;
        if (@is_file((PATH_typo3conf . 'LOCK_BACKEND'))) {
            unlink(PATH_typo3conf . 'LOCK_BACKEND');
            if (@is_file((PATH_typo3conf . 'LOCK_BACKEND'))) {
                $this->outputLine('ERROR: Could not remove lock file \'typo3conf/LOCK_BACKEND\'!');
                $this->sendAndExit(1);
            } else {
                $this->outputLine('Removed lock file \'typo3conf/LOCK_BACKEND\'');
            }
        } else {
            if (!$lockedForAdmins) {
                $this->outputLine('No lock file \'typo3conf/LOCK_BACKEND\' was found, hence no lock could be removed.');
                $this->sendAndExit(2);
            }
        }
        if ($lockedForAdmins) {
            $this->configurationService->setLocal('BE/adminOnly', self::LOCK_TYPE_UNLOCKED);
            $this->outputLine('Unlocked backend from admin only access!');
        }
    }
}
