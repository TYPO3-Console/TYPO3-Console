<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Hook;

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

use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook
 */
class ExtensionInstallation
{
    const BINARY_PATH = '/typo3conf/ext/typo3_console/Libraries/helhum/typo3-console/';
    const COPY_FAILED_MESSAGE_TITLE = 'Could not copy %s script to TYPO3 root directory (%s)!';
    const COPY_FAILED_MESSAGE = 'Check the permissions of your root directory. Is there a file or directory named %s inside this directory?';
    const COPY_SUCCESS_MESSAGE = 'Successfully copied the %s script to TYPO3 root directory. Let\'s dance!';
    const EXTKEY = 'typo3_console';
    const EM_FLASH_MESSAGE_QUEUE_ID = 'extbase.flashmessages.tx_extensionmanager_tools_extensionmanagerextensionmanager';
    // Replicate Application::COMMAND_NAME to avoid class loading issues in non composer mode
    const COMMAND_NAME = 'typo3cms';

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
        $scriptName = $this->isWindowsOs() ? 'Scripts/' . self::COMMAND_NAME . '.bat' : self::COMMAND_NAME;
        $success = $this->safeCopy(Environment::getPublicPath() . self::BINARY_PATH . $scriptName, Environment::getPublicPath() . '/' . basename($scriptName));
        if (!$success) {
            self::addFlashMessage(sprintf(self::COPY_FAILED_MESSAGE, $scriptName), sprintf(self::COPY_FAILED_MESSAGE_TITLE, $scriptName, Environment::getPublicPath()), AbstractMessage::WARNING);
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
     * @throws \TYPO3\CMS\Core\Exception
     * @throws \InvalidArgumentException if the message body is no string
     * @return void
     */
    protected function addFlashMessage($messageBody, $messageTitle = '', $severity = AbstractMessage::OK, $storeInSession = true)
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        if (!is_string($messageBody)) {
            throw new \InvalidArgumentException('The message body must be of type string, "' . gettype($messageBody) . '" given.', 1418250286);
        }
        $flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $messageBody, $messageTitle, $severity, $storeInSession);
        $queue = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageQueue::class, self::EM_FLASH_MESSAGE_QUEUE_ID);
        $queue->enqueue($flashMessage);
    }

    /**
     * Copy typo3 console binary to root directory taking several possible situations into account
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
            if (!self::isTypo3CmsBinary($fullTargetPath)) {
                // File is there but does not seem to be a previous version of our script: better ignore
                return false;
            }
        }
        if ($this->isWindowsOs()) {
            $success = @copy($fullSourcePath, $fullTargetPath);
        } else {
            $proxyFileContent = file_get_contents($fullSourcePath);
            $proxyFileContent = str_replace(
                'require __DIR__ . \'/Scripts/typo3-console.php\';',
                '// In non Composer mode we\'re copied into TYPO3 web root
require __DIR__ . \'/typo3conf/ext/typo3_console/Libraries/helhum/typo3-console/Scripts/typo3-console.php\';',
                $proxyFileContent
            );
            $success = file_put_contents($fullTargetPath, $proxyFileContent);
        }

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
        $binaryContent = file_get_contents($fullTargetPath);

        return strpos($binaryContent, 'typo3cms.php') !== false
            || strpos($binaryContent, 'typo3-console.php') !== false;
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
