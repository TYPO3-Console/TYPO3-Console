<?php
declare(strict_types=1);
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

use Composer\Script\Event;
use Composer\Script\Event as ScriptEvent;
use Composer\Semver\Constraint\EmptyConstraint;
use Helhum\Typo3Console\Composer\InstallerScript\CopyTypo3Directory;
use Helhum\Typo3Console\Composer\InstallerScript\GeneratePackageStates;
use Helhum\Typo3Console\Composer\InstallerScript\InstallDummyExtension;
use Helhum\Typo3Console\Composer\InstallerScript\PopulateCommandConfiguration;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScripts\AutoloadConnector;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScripts\WebDirectory;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScriptsRegistration;
use TYPO3\CMS\Composer\Plugin\Core\ScriptDispatcher;

/**
 * Scripts executed on composer build time
 */
class InstallerScripts implements InstallerScriptsRegistration
{
    /**
     * Allows to register one or more script objects that implement this interface
     * This will be called in the Plugin right before the scripts are executed.
     *
     * @param Event $event
     * @param ScriptDispatcher $scriptDispatcher
     * @return void
     */
    public static function register(Event $event, ScriptDispatcher $scriptDispatcher)
    {
        $scriptDispatcher->addInstallerScript(new PopulateCommandConfiguration(), 70);
        if (!class_exists(\TYPO3\CMS\Core\Composer\InstallerScripts::class)
            && !class_exists(\Helhum\Typo3ComposerSetup\Composer\InstallerScripts::class)
            && $event->getComposer()->getRepositoryManager()->getLocalRepository()->findPackage('typo3/cms', new EmptyConstraint()) !== null
        ) {
            // @deprecated can be removed once TYPO3 8 support is removed
            $scriptDispatcher->addInstallerScript(new WebDirectory());
            $scriptDispatcher->addInstallerScript(new AutoloadConnector());
            $scriptDispatcher->addInstallerScript(new CopyTypo3Directory());
        }
        $scriptDispatcher->addInstallerScript(new GeneratePackageStates(), 65);
        $scriptDispatcher->addInstallerScript(new InstallDummyExtension(), 65);
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
