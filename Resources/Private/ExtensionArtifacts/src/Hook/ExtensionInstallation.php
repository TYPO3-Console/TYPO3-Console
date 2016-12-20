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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook
 */
class ExtensionInstallation
{
    const BINARY_PATH = 'typo3conf/ext/typo3_console/Scripts/';
    const COPY_FAILED_MESSAGE_TITLE = 'Could not copy %s script to TYPO3 root directory (%s)!';
    const COPY_FAILED_MESSAGE = 'Check the permissions of your root directory. Is there a file or directory named %s inside this directory?';
    const COPY_SUCCESS_MESSAGE = 'Successfully copied the %s script to TYPO3 root directory. Let\'s dance!';
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
        $success = $this->safeCopy(PATH_site . self::BINARY_PATH . $scriptName, PATH_site . $scriptName);
        if (!$success) {
            self::addFlashMessage(sprintf(self::COPY_FAILED_MESSAGE, $scriptName), sprintf(self::COPY_FAILED_MESSAGE_TITLE, $scriptName, PATH_site), AbstractMessage::WARNING);
        } else {
            self::addFlashMessage(sprintf(self::COPY_SUCCESS_MESSAGE, $scriptName));
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

    /**
     * Copy typo3cms command to root directory taking several possible situations into account
     *
     * @param string $fullSourcePath Path to the script that should be copied (depending on OS)
     * @param string $fullTargetPath Target path to which the script should be copied to
     * @param string $relativeWebDir Relative path to the web directory (which equals the TYPO3 root directory currently)
     * @return bool
     * @internal
     */
    private function safeCopy($fullSourcePath, $fullTargetPath, $relativeWebDir = '')
    {
        if (file_exists($fullTargetPath)) {
            if (!is_file($fullTargetPath)) {
                // Seems to be a directory: ignore
                return false;
            }
            if (md5_file($fullTargetPath) === md5_file($fullSourcePath)) {
                // File is there: gladly ignore
                return true;
            }
            if (!self::isTypo3CmsBinary($fullTargetPath)) {
                // File is there but does not seem to be a previous version of our script: better ignore
                return false;
            }
        }
        $success = @copy($fullSourcePath, $fullTargetPath);
        if ($success && !$this->isWindowsOs()) {
            $success = @chmod($fullTargetPath, 0755);
        }
        if ($success) {
            $success = @file_put_contents(
                $fullTargetPath,
                str_replace(
                    '{$relative-web-dir}',
                    $relativeWebDir,
                    file_get_contents($fullTargetPath)
                )
            );
        }
        return $success;
    }

    protected static function isTypo3CmsBinary($fullTargetPath)
    {
        return strpos(file_get_contents($fullTargetPath), 'typo3cms.php') !== false;
    }

    /**
     * Returns true if PHP runs on Windows OS
     *
     * @return bool
     */
    private function isWindowsOs()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return true;
        }
        return false;
    }
}
