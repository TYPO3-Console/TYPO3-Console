<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Composer\InstallerScript;

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

use Composer\Script\Event as ScriptEvent;
use Helhum\Typo3ConsolePlugin\Config as PluginConfig;
use TYPO3\CMS\Composer\Plugin\Config as Typo3Config;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScript;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

/**
 * @deprecated will be removed with 5.0
 */
class InstallDummyExtension implements InstallerScript
{
    /**
     * @param ScriptEvent $event
     * @return bool
     */
    private function shouldRun(ScriptEvent $event): bool
    {
        return $event->getComposer()->getPackage()->getName() !== 'helhum/typo3-console';
    }

    /**
     * @param ScriptEvent $event
     * @throws \RuntimeException
     * @return bool
     * @internal
     */
    public function run(ScriptEvent $event): bool
    {
        if (!$this->shouldRun($event)) {
            return true;
        }

        $io = $event->getIO();
        $composer = $event->getComposer();

        $composerConfig = $composer->getConfig();
        $typo3Config = Typo3Config::load($composer);
        $pluginConfig = PluginConfig::load($io, $composerConfig);

        $webDir = $typo3Config->get('root-dir');
        $filesystem = new Filesystem();
        $extensionDir = "$webDir/typo3conf/ext/typo3_console";

        if ($pluginConfig->get('install-extension-dummy')) {
            $io->writeError('<warning>Installation of TYPO3 extension has been deprecated</warning>');
            $io->writeError('<warning>To get rid of this message, set "install-extension-dummy" option to false</warning>');
            $io->writeError('<warning>Use the following command to set this option:</warning>');
            $io->writeError('<warning>composer config extra.helhum/typo3-console.install-extension-dummy 0</warning>');

            $extResourcesDir = __DIR__ . '/../../../Resources/Private/ExtensionArtifacts';
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
        return true;
    }
}
