<?php
namespace Helhum\Typo3Console\Command;

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

use Helhum\Typo3Console\Mvc\Controller\CommandController;

/**
 * Commands for (un)restricting backend access.
 */
class BackendCommandController extends CommandController
{
    /**
     * Lock backend
     *
     * Deny backend access for <b>every</b> user (including admins).
     *
     * @param string $redirectUrl URL to redirect to when the backend is accessed
     * @see typo3_console:backend:unlock
     */
    public function lockCommand($redirectUrl = null)
    {
        if (@is_file(PATH_typo3conf . 'LOCK_BACKEND')) {
            $this->outputLine('<warning>Backend is already locked.</warning>');
            $this->quit(0);
        }
        \TYPO3\CMS\Core\Utility\GeneralUtility::writeFile(PATH_typo3conf . 'LOCK_BACKEND', (string)$redirectUrl);
        if (!@is_file(PATH_typo3conf . 'LOCK_BACKEND')) {
            $this->outputLine('<error>Could not create lock file \'typo3conf/LOCK_BACKEND\'.</error>');
            $this->quit(2);
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
     * Allow backend access again (e.g. after having been locked with backend:lock command).
     * @see typo3_console:backend:lock
     */
    public function unlockCommand()
    {
        if (!@is_file(PATH_typo3conf . 'LOCK_BACKEND')) {
            $this->outputLine('<warning>Backend is already unlocked.</warning>');
            $this->quit(0);
        }
        unlink(PATH_typo3conf . 'LOCK_BACKEND');
        if (@is_file(PATH_typo3conf . 'LOCK_BACKEND')) {
            $this->outputLine('<error>Could not remove lock file \'typo3conf/LOCK_BACKEND\'.</error>');
            $this->quit(2);
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
     * Lock backend for editors
     *
     * Deny backend access, but only for editors.
     * Admins will still be able to log in and work with the backend.
     *
     * @see typo3_console:backend:unlockforeditors
     */
    public function lockForEditorsCommand()
    {
        $this->ensureConfigValueModifiable();
        $lockedForEditors = $this->configurationService->getLocal('BE/adminOnly') !== self::LOCK_TYPE_UNLOCKED;
        if (!$lockedForEditors) {
            $this->configurationService->setLocal('BE/adminOnly', self::LOCK_TYPE_ADMIN);
            $this->outputLine('<info>Locked backend for editor access.</info>');
        } else {
            $this->outputLine('<warning>The backend was already locked for editors, hence nothing was done.</warning>');
        }
    }

    /**
     * Unlock backend for editors
     *
     * Allow backend access for editors again (e.g. after having been locked with backend:lockforeditors command).
     *
     * @see typo3_console:backend:lockforeditors
     */
    public function unlockForEditorsCommand()
    {
        $this->ensureConfigValueModifiable();
        $lockedForEditors = $this->configurationService->getLocal('BE/adminOnly') !== self::LOCK_TYPE_UNLOCKED;
        if ($lockedForEditors) {
            $this->configurationService->setLocal('BE/adminOnly', self::LOCK_TYPE_UNLOCKED);
            $this->outputLine('<info>Unlocked backend for editors.</info>');
        } else {
            $this->outputLine('<warning>The backend was not locked for editors.</warning>');
        }
    }

    /**
     * Checks whether the value can be set in LocalConfiguration.php and exits with error code if not.
     */
    protected function ensureConfigValueModifiable()
    {
        if (!$this->configurationService->localIsActive('BE/adminOnly')) {
            $this->outputLine('<error>The configuration value BE/adminOnly is not modifiable. Is it forced to a value in Additional Configuration?</error>');
            $this->quit(2);
        }
    }
}
