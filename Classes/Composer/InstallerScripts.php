<?php
namespace Helhum\Typo3Console\Composer;

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

use Composer\Script\Event as ScriptEvent;
use TYPO3\CMS\Composer\Plugin\Config;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

/**
 * Class for Composer and Extension Manager install scripts
 */
class InstallerScripts
{
    const BINARY_PATH = 'typo3conf/ext/typo3_console/Scripts/';
    const COPY_FAILED_MESSAGE_TITLE = 'Could not copy %s script to TYPO3 root directory (%s)!';
    const COPY_FAILED_MESSAGE = 'Check the permissions of your root directory. Is there a file or directory named %s inside this directory?';
    const COPY_SUCCESS_MESSAGE = 'Successfully copied the %s script to TYPO3 root directory. Let\'s dance!';

    /**
     * Called from composer
     *
     * @param ScriptEvent $event
     * @param bool $calledFromPlugin
     * @return void
     * @throws \RuntimeException
     * @internal
     */
    public static function setupConsole(ScriptEvent $event, $calledFromPlugin = false)
    {
        if ($event->getComposer()->getPackage()->getName() === 'helhum/typo3-console') {
            return;
        }
        if (!$calledFromPlugin) {
            // @deprecated
            $event->getIO()->writeError('<warning>Usage of Helhum\Typo3Console\Composer\InstallerScripts::setupConsole is deprecated. Please remove this section from your root composer.json</warning>');
            return;
        }

        $config = self::getConfig($event);
        $installDir = self::getInstallDir($config);
        $webDir = self::getWebDir($config);
        $filesystem = new Filesystem();

        $pluginConfig = \Helhum\Typo3ConsolePlugin\Config::load($event->getIO(), $event->getComposer()->getConfig());
        if (!$pluginConfig->get('install-binary')) {
            return;
        }
        $binDir = trim(substr($event->getComposer()->getConfig()->get('bin-dir'), strlen($config->getBaseDir())), '/');
        // @deprecated. can be removed once the typo3 installer takes care of installing binaries
        $event->getIO()->writeError('<warning>Usage of "./typo3cms" binary has been deprecated.</warning>');
        $event->getIO()->writeError('<warning>Please use ' . $binDir . '/typo3cms instead.</warning>');
        $event->getIO()->writeError('<warning>To get rid of this message, set "install-binary" option to false</warning>');
        $event->getIO()->writeError('<warning>in "extra -> helhum/typo3-console" section of root composer.json.</warning>');
        $pathToScriptsDirectory = __DIR__ . '/../../Scripts/';
        if (self::isWindowsOs()) {
            $scriptName = 'typo3cms.bat';
            $success = self::safeCopy($pathToScriptsDirectory . $scriptName, $webDir . '/' . $scriptName);
        } else {
            $scriptName = 'typo3cms';
            $targetPath = $installDir . '/' . $scriptName;
            $success = true;
            if (file_exists($targetPath)) {
                $success = false;
                if (self::isTypo3CmsBinary($targetPath)) {
                    $success = @unlink($targetPath);
                }
            }
            if ($success) {
                $filesystem->symlink($pathToScriptsDirectory . $scriptName, $targetPath, false);
            }
        }
        if (!$success) {
            $event->getIO()->writeError('<error>' . sprintf(self::COPY_FAILED_MESSAGE_TITLE, $scriptName, $installDir) . '</error>');
            $event->getIO()->writeError('<error>' . sprintf(self::COPY_FAILED_MESSAGE, $scriptName) . '</error>');
        }
    }

    /**
     * @param ScriptEvent $event
     * @internal
     * @throws \RuntimeException
     */
    public static function setVersion(ScriptEvent $event)
    {
        $version = $event->getArguments()[0];
        if (!preg_match('/\d+\.\d+\.\d+/', $version)) {
            throw new \RuntimeException('No valid version number provided!', 1468672604);
        }
        $docConfigFile = __DIR__ . '/../../Documentation/Settings.yml';
        $content = file_get_contents($docConfigFile);
        $content = preg_replace('/(version|release): \d+\.\d+\.\d+/', '$1: ' . $version, $content);
        file_put_contents($docConfigFile, $content);

        $extEmConfFile = __DIR__ . '/../../Resources/Private/ExtensionArtifacts/ext_emconf.php';
        $content = file_get_contents($extEmConfFile);
        $content = preg_replace('/(\'version\' => )\'\d+\.\d+\.\d+/', '$1\'' . $version, $content);
        file_put_contents($extEmConfFile, $content);

        $helpCommandFile = __DIR__ . '/../Command/HelpCommandController.php';
        $content = file_get_contents($helpCommandFile);
        $content = preg_replace('/(private \$version = )\'\d+\.\d+\.\d+/', '$1\'' . $version, $content);
        file_put_contents($helpCommandFile, $content);
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
    public static function safeCopy($fullSourcePath, $fullTargetPath, $relativeWebDir = '')
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
        return strpos(file_get_contents($fullTargetPath), 'typo3cms.php') !== false;
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

    /**
     * @deprecated This never was public API, just use EM
     */
    public static function postInstallExtension()
    {
    }

    /**
     * @deprecated This never was public API, just use your own flash message queue
     */
    public static function addFlashMessage()
    {
    }
}
