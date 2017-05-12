<?php
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
use Helhum\Typo3ConsolePlugin\InstallerScriptInterface;
use TYPO3\CMS\Composer\Plugin\Config as Typo3Config;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

class CopyTypo3Directory implements InstallerScriptInterface
{
    /**
     * @param ScriptEvent $event
     * @return bool
     */
    public function shouldRun(ScriptEvent $event)
    {
        // Only run on Windows and only when we are the root package (made for Appveyor tests)
        return DIRECTORY_SEPARATOR === '\\' && (getenv('TYPO3_CONSOLE_SUB_PROCESS') || $event->getComposer()->getPackage()->getName() === 'helhum/typo3-console');
    }

    /**
     * @param ScriptEvent $event
     * @throws \RuntimeException
     * @return bool
     * @internal
     */
    public function run(ScriptEvent $event)
    {
        $io = $event->getIO();
        $composer = $event->getComposer();
        $typo3Config = Typo3Config::load($composer);
        $webDir = $typo3Config->get('web-dir');
        $typo3Dir = $typo3Config->get('cms-package-dir');
        if (is_link("$webDir/typo3")) {
            rmdir("$webDir/typo3");
            $filesystem = new Filesystem();
            $filesystem->copy("$typo3Dir/typo3", "$webDir/typo3");
            $io->write('<comment>Copied typo3 directory to document root</comment>');
        }
        return true;
    }
}
