<?php
declare(strict_types=1);
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
use Helhum\Typo3Console\Service\Configuration\ConfigurationService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;
use TYPO3\CMS\Saltedpasswords\Salt\SaltInterface;

/**
 * Commands for (un)restricting backend access and creating admin user
 */
class BackendCommandController extends CommandController
{
    const LOCK_TYPE_UNLOCKED = 0;
    const LOCK_TYPE_ADMIN = 2;

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * @var SaltInterface
     */
    private $salt;

    public function __construct(ConfigurationService $configurationService, SaltInterface $salt = null)
    {
        $this->configurationService = $configurationService;
        $this->salt = $salt ?: SaltFactory::getSaltingInstance(null, 'BE');
    }

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
     * Create admin backend user
     *
     * Create a new user with administrative access.
     *
     * @param string $username Username of the user
     * @param string $password Password of the user
     */
    public function createAdminCommand(string $username, string $password)
    {
        $givenUsername = $username;
        $username = strtolower(preg_replace('/\\s/i', '', $username));

        if ($givenUsername !== $username) {
            $this->outputLine('<warning>Given username "%s" contains invalid characters. Using "%s" instead.</warning>', [$givenUsername, $username]);
        }

        if (strlen($username) < 4) {
            $this->outputLine('<error>Username must be at least 4 characters.</error>');
            $this->quit(1);
        }
        if (strlen($password) < 8) {
            $this->outputLine('<error>Password must be at least 8 characters.</error>');
            $this->quit(1);
        }
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $userExists = $connectionPool->getConnectionForTable('be_users')
            ->count(
                'uid',
                'be_users',
                ['username' => $username]
            );
        if ($userExists) {
            $this->outputLine('<error>A user with username "%s" already exists.</error>', [$username]);
            $this->quit(1);
        }
        $adminUserFields = [
            'username' => $username,
            'password' => $this->salt->getHashedPassword($password),
            'admin' => 1,
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'crdate' => $GLOBALS['EXEC_TIME'],
        ];
        $connectionPool->getConnectionForTable('be_users')
            ->insert('be_users', $adminUserFields);

        $this->outputLine('<info>Created admin user with username "%s".</info>', [$username]);
    }

    /**
     * Checks whether the value can be set in LocalConfiguration.php and exits with error code if not.
     */
    private function ensureConfigValueModifiable()
    {
        if (!$this->configurationService->localIsActive('BE/adminOnly')) {
            $this->outputLine('<error>The configuration value BE/adminOnly is not modifiable. Is it forced to a value in Additional Configuration?</error>');
            $this->quit(2);
        }
    }
}
