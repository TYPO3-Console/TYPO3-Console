<?php

namespace Helhum\Typo3Console\Composer;

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

use Composer\Script\CommandEvent;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for Composer and Extension Manager install scripts.
 */
class InstallerScripts
{
    const BINARY_PATH = 'typo3conf/ext/typo3_console/Scripts/';
    const EM_FLASH_MESSAGE_QUEUE_ID = 'extbase.flashmessages.tx_extensionmanager_tools_extensionmanagerextensionmanager';
    const COPY_FAILED_MESSAGE_TITLE = 'Could not copy %s script to TYPO3 root directory!';
    const COPY_FAILED_MESSAGE = 'Permission problem? Is there a file or directory named %s? Please copy it manually now to get the console command.';
    const COPY_SUCCESS_MESSAGE = 'Successfully copied the %s script to TYPO3 root directory. Let\'s dance!';

    /**
     * Called from composer.
     *
     * @param CommandEvent $event
     *
     * @return void
     */
    public static function postUpdateAndInstall(CommandEvent $event)
    {
        $scriptName = self::isWindowsOs() ? 'typo3cms.bat' : 'typo3cms';
        $success = self::safeCopy($scriptName, './');
        if (!$success) {
            $event->getIO()->write(sprintf(self::COPY_FAILED_MESSAGE_TITLE, $scriptName));
            $event->getIO()->write(sprintf(self::COPY_FAILED_MESSAGE, $scriptName));
        }
    }

    /**
     * Called from TYPO3 CMS extension manager.
     */
    public static function postInstallExtension()
    {
        $scriptName = self::isWindowsOs() ? 'typo3cms.bat' : 'typo3cms';
        $success = self::safeCopy($scriptName, PATH_site);
        if (!$success) {
            self::addFlashMessage(sprintf(self::COPY_FAILED_MESSAGE, $scriptName), sprintf(self::COPY_FAILED_MESSAGE_TITLE, $scriptName), AbstractMessage::WARNING);
        } else {
            self::addFlashMessage(sprintf(self::COPY_SUCCESS_MESSAGE, $scriptName));
        }
    }

    /**
     * Copy typo3cms command to root directory taking several possible situations into account.
     *
     * @param string $scriptName The script that should be copied (depending on OS)
     * @param string $pathPrefix directory prefix
     *
     * @return bool
     */
    protected static function safeCopy($scriptName, $pathPrefix)
    {
        $fullSourcePath = $pathPrefix.self::BINARY_PATH.$scriptName;
        $fullTargetPath = $pathPrefix.$scriptName;

        if (file_exists($fullTargetPath)) {
            if (!is_file($fullTargetPath)) {
                // Seems to be a directory: ignore
                return false;
            }
            if (md5_file($fullTargetPath) === md5_file($fullSourcePath)) {
                // File is there: gladly ignore
                return true;
            }
            if (strpos(file_get_contents($fullTargetPath), 'typo3cms.php') === false) {
                // File is there but does not seem to be a previous version of our script: better ignore
                return false;
            }
        }

        $success = @copy($fullSourcePath, $fullTargetPath);

        if ($success && !self::isWindowsOs()) {
            $success = @chmod($fullTargetPath, 0755);
        }

        return $success;
    }

    /**
     * Returns true if PHP runs on Windows OS.
     *
     * @return bool
     */
    protected static function isWindowsOs()
    {
        if (!stristr(PHP_OS, 'darwin') && stristr(PHP_OS, 'win')) {
            return true;
        }

        return false;
    }

    /**
     * Creates a Message object and adds it to the FlashMessageQueue.
     *
     * @param string $messageBody    The message
     * @param string $messageTitle   Optional message title
     * @param int    $severity       Optional severity, must be one of \TYPO3\CMS\Core\Messaging\FlashMessage constants
     * @param bool   $storeInSession Optional, defines whether the message should be stored in the session (default) or not
     *
     * @throws \InvalidArgumentException if the message body is no string
     *
     * @return void
     */
    public static function addFlashMessage($messageBody, $messageTitle = '', $severity = AbstractMessage::OK, $storeInSession = true)
    {
        if (!is_string($messageBody)) {
            throw new \InvalidArgumentException('The message body must be of type string, "'.gettype($messageBody).'" given.', 1418250286);
        }
        $flashMessage = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $messageBody, $messageTitle, $severity, $storeInSession);
        $queue = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageQueue', self::EM_FLASH_MESSAGE_QUEUE_ID);
        $queue->enqueue($flashMessage);
    }
}
