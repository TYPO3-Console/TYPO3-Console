<?php
namespace Helhum\Typo3Console\Composer;

/*
 * This file is part of the typo3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

use Composer\Script\Event as ScriptEvent;
use TYPO3\CMS\Composer\Plugin\Config;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for Composer and Extension Manager install scripts
 */
class InstallerScripts
{
    const BINARY_PATH = 'typo3conf/ext/typo3_console/Scripts/';
    const EM_FLASH_MESSAGE_QUEUE_ID = 'extbase.flashmessages.tx_extensionmanager_tools_extensionmanagerextensionmanager';
    const COPY_FAILED_MESSAGE_TITLE = 'Could not copy %s script to TYPO3 root directory (%s)!';
    const COPY_FAILED_MESSAGE = 'Check the permissions of your root directory. Is there a file or directory named %s inside this directory?';
    const COPY_SUCCESS_MESSAGE = 'Successfully copied the %s script to TYPO3 root directory. Let\'s dance!';

    /**
     * Called from composer
     *
     * @param ScriptEvent $event
     * @param bool $calledFromPlugin
     * @return void
     */
    public static function setupConsole(ScriptEvent $event, $calledFromPlugin = false)
    {
        if (!$calledFromPlugin) {
            $event->getIO()->write('<comment>Usage of Helhum\Typo3Console\Composer\InstallerScripts::setupConsole is deprecated. Please remove this section from your root composer.json</comment>');
            return;
        }

        $config = self::getConfig($event);
        $installDir = self::getInstallDir($config);
        $webDir = self::getWebDir($config);
        $filesystem = new Filesystem();

        // Special treatment if we are root package (for development and testing)
        if ($event->getComposer()->getPackage()->getName() === 'helhum/typo3-console') {
            $extDir = $webDir . '/typo3conf/ext';
            $consoleDir = $extDir . '/typo3_console';
            if (!file_exists($consoleDir)) {
                $filesystem->ensureDirectoryExists($extDir);
                $filesystem->symlink($installDir, $consoleDir);
            }
        }
        if (self::isWindowsOs()) {
            $scriptName = 'typo3cms.bat';
            $success = self::safeCopy($webDir . '/' . self::BINARY_PATH . $scriptName, $webDir . '/' . $scriptName);
        } else {
            $scriptName = 'typo3cms';
            $targetPath = $installDir . '/' . $scriptName;
            if (file_exists($targetPath) && self::isTypo3CmsBinary($targetPath)) {
                $success = @unlink($targetPath);
            } else {
                $success = true;
            }
            if ($success) {
                $filesystem->symlink($webDir . '/' . self::BINARY_PATH . $scriptName, $targetPath, false);
            }
        }
        if (!$success) {
            $event->getIO()->write('<error>' . sprintf(self::COPY_FAILED_MESSAGE_TITLE, $scriptName, $installDir) . '</error>');
            $event->getIO()->write('<error>' . sprintf(self::COPY_FAILED_MESSAGE, $scriptName) . '</error>');
        }
    }

    /**
     * Called from TYPO3 CMS extension manager
     */
    public static function postInstallExtension()
    {
        if (Bootstrap::usesComposerClassLoading()) {
            // Composer has done its job already. Nothing to do for us here!
            return;
        }
        $scriptName = self::isWindowsOs() ? 'typo3cms.bat' : 'typo3cms';
        $success = self::safeCopy(PATH_site . self::BINARY_PATH . $scriptName, PATH_site . $scriptName);
        if (!$success) {
            self::addFlashMessage(sprintf(self::COPY_FAILED_MESSAGE, $scriptName), sprintf(self::COPY_FAILED_MESSAGE_TITLE, $scriptName, PATH_site), AbstractMessage::WARNING);
        } else {
            self::addFlashMessage(sprintf(self::COPY_SUCCESS_MESSAGE, $scriptName));
        }
    }

    /**
     * Copy typo3cms command to root directory taking several possible situations into account
     *
     * @param string $fullSourcePath Path to the script that should be copied (depending on OS)
     * @param string $fullTargetPath Target path to which the script should be copied to
     * @param string $relativeWebDir Relative path to the web directory (which equals the TYPO3 root directory currently)
     * @return bool
     */
    protected static function safeCopy($fullSourcePath, $fullTargetPath, $relativeWebDir = '')
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
            if (strpos(file_get_contents($fullTargetPath), 'typo3cms.php') === false) {
                // File is there but does not seem to be a previous version of our script: better ignore
                return false;
            }
        }
        $success = @copy($fullSourcePath, $fullTargetPath);
        if ($success && !self::isWindowsOs()) {
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
        if (is_link($fullTargetPath) || strpos(file_get_contents($fullTargetPath), 'typo3cms.php') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Returns true if PHP runs on Windows OS
     *
     * @return bool
     */
    protected static function isWindowsOs()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return true;
        }
        return false;
    }

    /**
     * Creates a Message object and adds it to the FlashMessageQueue.
     *
     * @param string $messageBody The message
     * @param string $messageTitle Optional message title
     * @param int $severity Optional severity, must be one of \TYPO3\CMS\Core\Messaging\FlashMessage constants
     * @param bool $storeInSession Optional, defines whether the message should be stored in the session (default) or not
     * @return void
     * @throws \InvalidArgumentException if the message body is no string
     */
    public static function addFlashMessage($messageBody, $messageTitle = '', $severity = AbstractMessage::OK, $storeInSession = true)
    {
        if (!is_string($messageBody)) {
            throw new \InvalidArgumentException('The message body must be of type string, "' . gettype($messageBody) . '" given.', 1418250286);
        }
        $flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $messageBody, $messageTitle, $severity, $storeInSession);
        $queue = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageQueue::class, self::EM_FLASH_MESSAGE_QUEUE_ID);
        $queue->enqueue($flashMessage);
    }

    /**
     * @param Config $config
     * @return string
     */
    protected static function getInstallDir(Config $config)
    {
        return realpath($config->getBaseDir());
    }

    /**
     * @param Config $config
     * @return string
     */
    protected static function getWebDir(Config $config)
    {
        return realpath($config->get('web-dir'));
    }

    /**
     * @param ScriptEvent $event
     * @return Config
     */
    protected static function getConfig(ScriptEvent $event)
    {
        return Config::load($event->getComposer());
    }
}
