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
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3ConsolePlugin\Config as PluginConfig;
use TYPO3\CMS\Composer\Plugin\Config as Typo3Config;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

/**
 * Legacy class for Composer and Extension Manager install scripts
 * @deprecated Will be removed with 5.0
 */
class LegacyInstallerScripts
{
    /**
     * Called from Composer Plugin
     *
     * @throws \RuntimeException
     * @return void
     * @internal
     */
    public static function setupConsole(ScriptEvent $event)
    {
        self::populateCommandConfiguration($event);
        self::generatePackageStates($event);
        self::installDummyExtension($event);
    }

    private static function populateCommandConfiguration(ScriptEvent $event)
    {
        $composer = $event->getComposer();
        $composerConfig = $composer->getConfig();
        $basePath = realpath(substr($composerConfig->get('vendor-dir'), 0, -strlen($composerConfig->get('vendor-dir', $composerConfig::RELATIVE_PATHS))));
        $commandConfiguration = [];
        foreach (self::extractPackageMapFromComposer($composer) as $item) {
            /** @var \Composer\Package\PackageInterface $package */
            list($package, $installPath) = $item;
            $installPath = ($installPath ?: $basePath);
            if (file_exists($commandConfigurationFile = $installPath . '/Configuration/Console/Commands.php')) {
                $commandConfiguration[$package->getName()] = require $commandConfigurationFile;
            }
        }
        file_put_contents(
            __DIR__ . '/../../Configuration/Console/AllCommands.php',
            '<?php' . chr(10)
            . 'return '
            . var_export($commandConfiguration, true)
            . ';'
        );
    }

    /**
     * @param \Composer\Composer $composer
     * @return array
     */
    private static function extractPackageMapFromComposer(\Composer\Composer $composer)
    {
        $mainPackage = $composer->getPackage();
        $autoLoadGenerator = $composer->getAutoloadGenerator();
        $localRepo = $composer->getRepositoryManager()->getLocalRepository();
        return $autoLoadGenerator->buildPackageMap($composer->getInstallationManager(), $mainPackage, $localRepo->getCanonicalPackages());
    }

    private static function generatePackageStates(ScriptEvent $event)
    {
        if (!getenv('TYPO3_CONSOLE_FEATURE_GENERATE_PACKAGE_STATES')) {
            return;
        }
        $io = $event->getIO();
        $composer = $event->getComposer();
        $pluginConfig = PluginConfig::load($io, $composer->getConfig());
        $typo3PluginConfig = Typo3Config::load($composer);

        if ($pluginConfig->get('skip-packagestates-write')) {
            $io->writeError('<warning>It is highly recommended to let the PackageStates.php file be generated automatically</warning>');
            $io->writeError('<warning>Disabling this functionality will be removed with TYPO3 Console 5.0</warning>');
            return;
        }

        if ($typo3PluginConfig->get('prepare-web-dir') === false) {
            return;
        }

        $io->writeError('<warning>Using TYPO3_CONSOLE_FEATURE_GENERATE_PACKAGE_STATES env var has been deprecated.</warning>');
        $io->writeError('<warning>Please abstain from using it and consider using "typo3-console/auto-setup" composer package instead.</warning>');

        $commandDispatcher = CommandDispatcher::createFromComposerRun($event);
        $commandOptions = [
            'frameworkExtensions' => (string)getenv('TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS'),
        ];
        if (getenv('TYPO3_ACTIVATE_DEFAULT_FRAMEWORK_EXTENSIONS')) {
            $commandOptions['activateDefault'] = true;
        }
        if ($event->isDevMode() && getenv('TYPO3_EXCLUDED_EXTENSIONS')) {
            $commandOptions['excludedExtensions'] = (string)getenv('TYPO3_EXCLUDED_EXTENSIONS');
        }
        $output = $commandDispatcher->executeCommand('install:generatepackagestates', $commandOptions);
        $io->writeError($output, true, $io::VERBOSE);
    }

    private static function installDummyExtension(ScriptEvent $event)
    {
        if ($event->getComposer()->getPackage()->getName() === 'helhum/typo3-console') {
            return;
        }
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
            $io->writeError('<warning>Use the following command to set this option:</warning>');
            $io->writeError('<warning>composer config extra.helhum/typo3-console.install-extension-dummy 0</warning>');

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
}
