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
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3ConsolePlugin\Config as PluginConfig;
use TYPO3\CMS\Composer\Plugin\Config as Typo3Config;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScript;

/**
 * @deprecated will be removed with 5.0
 */
class GeneratePackageStates implements InstallerScript
{
    /**
     * @param ScriptEvent $event
     * @return bool
     */
    private function shouldRun(ScriptEvent $event): bool
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

        $io->writeError('<warning>Using TYPO3_CONSOLE_FEATURE_GENERATE_PACKAGE_STATES env var has been deprecated.</warning>');
        $io->writeError('<warning>Please abstain from using it and consider using "typo3-console/auto-setup" composer package instead.</warning>');

        return true;
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
