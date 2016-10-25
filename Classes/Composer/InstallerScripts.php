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
use Helhum\Typo3ConsolePlugin\Config as PluginConfig;
use TYPO3\CMS\Composer\Plugin\Config as Typo3Config;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

/**
 * Class for Composer and Extension Manager install scripts
 */
class InstallerScripts
{
    /**
     * Called from composer
     *
     * @param ScriptEvent $event
     * @return void
     * @throws \RuntimeException
     * @internal
     */
    public static function setupConsole(ScriptEvent $event)
    {
        if ($event->getComposer()->getPackage()->getName() === 'helhum/typo3-console') {
            return;
        }
        self::installExtension($event);
    }

    /**
     * @param ScriptEvent $event
     * @deprecated will be removed with 5.0
     */
    private static function installExtension(ScriptEvent $event)
    {
        $io = $event->getIO();
        $composer = $event->getComposer();

        $composerConfig = $composer->getConfig();
        $typo3Config = Typo3Config::load($composer);
        $pluginConfig = PluginConfig::load($io, $composerConfig);

        $webDir = $typo3Config->get('web-dir');
        $filesystem = new Filesystem();
        $extensionDir = "$webDir/typo3conf/ext/typo3_console";

        if ($pluginConfig->get('install-extension-dummy')) {
            $io->writeError('<warning>Installation of TYPO3 extension has been deprecated</warning>');
            $io->writeError('<warning>To get rid of this message, set "install-extension-dummy" option to false</warning>');
            $io->writeError('<warning>in "extra -> helhum/typo3-console" section of root composer.json</warning>');

            $extResourcesDir = __DIR__ . '/../../Resources/Private/ExtensionArtifacts';
            $resources = [
                'ext_icon.png',
                'ext_emconf.php',
            ];
            foreach ($resources as $resource) {
                $target = "$extensionDir/$resource";
                $filesystem->ensureDirectoryExists(dirname($target));
                $filesystem->copy("$extResourcesDir/$resource", $target);
            }
            $io->writeError('<info>TYPO3 Console: Installed TYPO3 extension into TYPO3 extension directory</info>');
        } else {
            if (file_exists($extensionDir) || is_dir($extensionDir)) {
                $filesystem->removeDirectory($extensionDir);
                $io->writeError('<info>TYPO3 Console: Removed TYPO3 extension from TYPO3 extension directory</info>');
            }
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
}
