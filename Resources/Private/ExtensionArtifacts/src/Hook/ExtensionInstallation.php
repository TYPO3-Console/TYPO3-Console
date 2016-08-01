<?php
namespace Helhum\Typo3Console\Hook;

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

use Helhum\Typo3Console\Composer\InstallerScripts;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;
use TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility;

/**
 * Hook
 */
class ExtensionInstallation
{
    const EXTKEY = 'typo3_console';
    const EM_FLASH_MESSAGE_QUEUE_ID = 'extbase.flashmessages.tx_extensionmanager_tools_extensionmanagerextensionmanager';

    /**
     * Actions to take after extension has been installed
     *
     * @param string $keyOfInstalledExtension
     */
    public function afterInstallation($keyOfInstalledExtension)
    {
        if (self::EXTKEY !== $keyOfInstalledExtension) {
            return;
        }
        $scriptName = TYPO3_OS === 'WIN' ? 'typo3cms.bat' : 'typo3cms';
        $success = InstallerScripts::safeCopy(PATH_site . InstallerScripts::BINARY_PATH . $scriptName, PATH_site . $scriptName);
        if (!$success) {
            self::addFlashMessage(sprintf(InstallerScripts::COPY_FAILED_MESSAGE, $scriptName), sprintf(InstallerScripts::COPY_FAILED_MESSAGE_TITLE, $scriptName, PATH_site), AbstractMessage::WARNING);
        } else {
            self::addFlashMessage(sprintf(InstallerScripts::COPY_SUCCESS_MESSAGE, $scriptName));
        }
        $this->createCliBeUser();
    }

    protected function createCliBeUser()
    {
        $db = $this->getDatabaseConnection();

        $where = 'username = ' . $db->fullQuoteStr('_cli_lowlevel', 'be_users') . ' AND admin = 0 AND deleted = 0';

        $user = $db->exec_SELECTgetSingleRow('*', 'be_users', $where);
        if ($user) {
            if ($user['deleted'] || $user['disable']) {
                $data = array(
                    'be_users' => array(
                        $user['uid'] => array(
                            'deleted' => 0,
                            'disable' => 0
                        )
                    )
                );
                $dataHandler = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                $dataHandler->stripslashes_values = false;
                $dataHandler->start($data, array());
                $dataHandler->process_datamap();
            }
        } else {
            // Prepare necessary data for _cli_lowlevel user creation
            $password = GeneralUtility::getRandomHexString(48);
            if (SaltedPasswordsUtility::isUsageEnabled()) {
                $objInstanceSaltedPW = SaltFactory::getSaltingInstance();
                $password = $objInstanceSaltedPW->getHashedPassword($password);
            }
            $data = array(
                'be_users' => array(
                    'NEW' => array(
                        'username' => '_cli_lowlevel',
                        'password' => $password,
                        'pid' => 0
                    )
                )
            );
            $dataHandler = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
            $dataHandler->stripslashes_values = false;
            $dataHandler->start($data, array());
            $dataHandler->process_datamap();
            // Check if a new uid was indeed generated (i.e. a new record was created)
            // (counting DataHandler errors doesn't work as some failures don't report errors)
            $numberOfNewIDs = count($dataHandler->substNEWwithIDs);
            if ((int)$numberOfNewIDs !== 1) {
                $this->addFlashMessage('Failed to create _cli_lowlevel BE user.', 'BE user creation failed', AbstractMessage::WARNING);
            }
        }
    }

    /**
     * Creates a Message object and adds it to the FlashMessageQueue.
     *
     * @param string $messageBody The message
     * @param string $messageTitle Optional message title
     * @param int $severity Optional severity, must be one of \TYPO3\CMS\Core\Messaging\FlashMessage constants
     * @param bool $storeInSession Optional, defines whether the message should be stored in the session (default) or not
     * @return void
     * @throws \TYPO3\CMS\Core\Exception
     * @throws \InvalidArgumentException if the message body is no string
     */
    protected function addFlashMessage($messageBody, $messageTitle = '', $severity = AbstractMessage::OK, $storeInSession = true)
    {
        if (!is_string($messageBody)) {
            throw new \InvalidArgumentException('The message body must be of type string, "' . gettype($messageBody) . '" given.', 1418250286);
        }
        $flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $messageBody, $messageTitle, $severity, $storeInSession);
        $queue = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageQueue::class, self::EM_FLASH_MESSAGE_QUEUE_ID);
        $queue->enqueue($flashMessage);
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
