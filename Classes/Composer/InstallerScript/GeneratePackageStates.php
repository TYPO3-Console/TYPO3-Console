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
use Composer\Util\Filesystem;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3ConsolePlugin\Config as PluginConfig;
use Helhum\Typo3ConsolePlugin\InstallerScriptInterface;
use TYPO3\CMS\Composer\Plugin\Config as Typo3Config;

class GeneratePackageStates implements InstallerScriptInterface
{
    /**
     * @param ScriptEvent $event
     * @return bool
     */
    public function shouldRun(ScriptEvent $event)
    {
        if (!getenv('TYPO3_CONSOLE_FEATURE_GENERATE_PACKAGE_STATES')) {
            return false;
        }
        $io = $event->getIO();
        $composer = $event->getComposer();
        $pluginConfig = PluginConfig::load($io, $composer->getConfig());
        $typo3PluginConfig = Typo3Config::load($composer);

        if ($pluginConfig->get('skip-packagestates-write')) {
            $io->writeError('<warning>It is highly recommended to let the PackageStates.php file be generated automatically</warning>');
            $io->writeError('<warning>Disabling this functionality will be removed with TYPO3 Console 5.0</warning>');
            return false;
        }

        if ($typo3PluginConfig->get('prepare-web-dir') === false) {
            return false;
        }

        if (!getenv('TYPO3_CONSOLE_TEST_SETUP') && $composer->getPackage()->getName() === 'helhum/typo3-console') {
            return false;
        }

        // Ensure we have at least the typo3conf folder present
        (new Filesystem())->ensureDirectoryExists($typo3PluginConfig->get('web-dir') . '/typo3conf');
        return true;
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

        return true;
    }
}
