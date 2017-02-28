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
use Helhum\Typo3Console\Composer\InstallerScript\GeneratePackageStates;
use Helhum\Typo3Console\Composer\InstallerScript\InstallDummyExtension;
use Helhum\Typo3Console\Composer\InstallerScript\PopulateCommandConfiguration;

/**
 * Class for Composer and Extension Manager install scripts
 */
class InstallerScripts
{
    /**
     * Scripts to execute when console is set up
     *
     * @var array
     */
    private static $scripts = [
        PopulateCommandConfiguration::class,
        GeneratePackageStates::class,
        InstallDummyExtension::class,
    ];

    /**
     * Called from Composer
     *
     * @param ScriptEvent $event
     * @throws \RuntimeException
     * @return void
     * @internal
     */
    public static function setupConsole(ScriptEvent $event)
    {
        $io = $event->getIO();
        foreach (self::$scripts as $scriptClass) {
            /** @var InstallerScriptInterface $script */
            $script = new $scriptClass();
            if ($script->shouldRun($event)) {
                $io->writeError(sprintf('<info>Executing "%s": </info>', $scriptClass), true, $io::DEBUG);
                if (!$script->run($event)) {
                    $io->writeError(sprintf('<error>Executing "%s" failed!</error>', $scriptClass), true);
                }
            } else {
                $io->writeError(sprintf('<info>Skipped executing "%s": </info>', $scriptClass), true, $io::DEBUG);
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
