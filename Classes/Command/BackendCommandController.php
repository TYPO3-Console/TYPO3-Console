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
 * Commands for (un)restricting backend access.
 *
 * = Examples for global lock and unlock =
 *
 * <code>./typo3cms backend:lock</code> Every request to TYPO3 backend will be denied.
 * <code>./typo3cms backend:lock 'http://domain.tld/maintenance.html' </code> Every request to TYPO3 backend will be denied and users will be redirected to given URL.
 * <code>./typo3cms backend:unlock</code> Makes the TYPO3 backend available again.
 *
 * = Examples for editor lock and unlock =
 *
 * <code>./typo3cms backend:lockforeditors</code> Editors won't be able to access the TYPO3 backend.
 * <code>./typo3cms backend:unlockforeditors</code> Editors can access the TYPO3 backend again.
 */
class BackendCommandController extends CommandController
{
    /**
     * Lock backend
     *
     * Restrict backend access for every user (including admins)
     *
     * @param string $redirectUrl URL to redirect to when the backend is accessed
     */
    public function lockCommand($redirectUrl = null)
    {
        if (@is_file(PATH_typo3conf . 'LOCK_BACKEND')) {
            $this->outputLine('<error>Backend is already locked!</error>');
            $this->sendAndExit(1);
        }
        \TYPO3\CMS\Core\Utility\GeneralUtility::writeFile(PATH_typo3conf . 'LOCK_BACKEND', (string)$redirectUrl);
        if (!@is_file(PATH_typo3conf . 'LOCK_BACKEND')) {
            $this->outputLine('<error>Could not create lock file \'typo3conf/LOCK_BACKEND\'!</error>');
            $this->sendAndExit(2);
        } else {
            $this->outputLine('<info>Backend has been locked. Access is denied for every user until it is unlocked again.</info>');
            if ($redirectUrl !== null) {
                $this->outputLine('Any access to the backend will be redirected to: \'' . $redirectUrl . '\'');
            }
        }
    }

    /**
     * Unlock backend
     *
     * Unlocks the backend access (after having been locked with
     * backend:lock for example)
     */
    public function unlockCommand()
    {
        if (!@is_file(PATH_typo3conf . 'LOCK_BACKEND')) {
            $this->outputLine('<error>Backend is already unlocked!</error>');
            $this->sendAndExit(1);
        }
        unlink(PATH_typo3conf . 'LOCK_BACKEND');
        if (@is_file(PATH_typo3conf . 'LOCK_BACKEND')) {
            $this->outputLine('<error>Could not remove lock file \'typo3conf/LOCK_BACKEND\'!</error>');
            $this->sendAndExit(2);
        } else {
            $this->outputLine('<info>Backend lock is removed. User can now access the backend again.</info>');
        }
    }

    const LOCK_TYPE_UNLOCKED = 0;
    const LOCK_TYPE_ADMIN = 2;

    /**
     * @var \Helhum\Typo3Console\Service\Configuration\ConfigurationService
     * @inject
     */
    protected $configurationService;

    /**
     * Lock backend (editors)
     *
     * Restrict backend access to admin only
     */
    public function lockForEditorsCommand()
    {
        $this->ensureConfigValueModifiable();
        $lockedForEditors =  $this->configurationService->getLocal('BE/adminOnly') !== self::LOCK_TYPE_UNLOCKED;
        if (!$lockedForEditors) {
            $this->configurationService->setLocal('BE/adminOnly', self::LOCK_TYPE_ADMIN);
            $this->outputLine('Locked backend for editor access!');
        } else {
            $this->outputLine('The backend was already locked for editors, hence nothing was done.');
            $this->sendAndExit(1);
        }
    }

    /**
     * Unlock backend (editors)
     *
     * Unlocks the backend access for editors
     */
    public function unlockForEditorsCommand()
    {
        $this->ensureConfigValueModifiable();
        $lockedForEditors = $this->configurationService->getLocal('BE/adminOnly') !== self::LOCK_TYPE_UNLOCKED;
        if ($lockedForEditors) {
            $this->configurationService->setLocal('BE/adminOnly', self::LOCK_TYPE_UNLOCKED);
            $this->outputLine('Unlocked backend for editors!');
        } else {
            $this->outputLine('The backend was not locked for editors, hence nothing was done.');
            $this->sendAndExit(1);
        }
    }

    /**
     * Checks whether the value can be set in LocalConfiguration.php and exits with error code if not.
     */
    protected function ensureConfigValueModifiable()
    {
        if (!$this->configurationService->localIsActive('BE/adminOnly')) {
            $this->outputLine('The configuration value BE/adminOnly is not modifiable. Is it forced to a value in Additional Configuration?');
            $this->sendAndExit(1);
        }
    }
}
