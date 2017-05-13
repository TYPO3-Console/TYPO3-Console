<?php
namespace Helhum\Typo3Console\Composer;

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
use Helhum\Typo3Console\Composer\InstallerScript\CopyTypo3Directory;
use Helhum\Typo3Console\Composer\InstallerScript\GeneratePackageStates;
use Helhum\Typo3Console\Composer\InstallerScript\InstallDummyExtension;
use Helhum\Typo3Console\Composer\InstallerScript\PopulateCommandConfiguration;
use Helhum\Typo3ConsolePlugin\ScriptDispatcher;

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
        100 => CopyTypo3Directory::class,
        90 => PopulateCommandConfiguration::class,
        80 => GeneratePackageStates::class,
        70 => InstallDummyExtension::class,
    ];

    /**
     * Called from Composer Plugin
     *
     * @throws \RuntimeException
     * @return void
     * @internal
     */
    public static function setupConsole()
    {
        foreach (self::$scripts as $priority => $scriptClass) {
            ScriptDispatcher::addInstallerScript($scriptClass, $priority);
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

        $travisYmlFile = __DIR__ . '/../../.travis.yml';
        $content = file_get_contents($travisYmlFile);
        $content = preg_replace('/(export COMPOSER_ROOT_VERSION)=\d+\.\d+\.\d+/', '$1=' . $version, $content);
        file_put_contents($travisYmlFile, $content);
    }
}
