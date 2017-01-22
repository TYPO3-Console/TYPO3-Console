<?php
namespace Helhum\Typo3Console\Composer\InstallerScript;

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
use Helhum\Typo3Console\Composer\InstallerScriptInterface;
use Helhum\Typo3Console\Core\ConsoleBootstrap;
use Helhum\Typo3Console\Install\PackageStatesGenerator;
use Helhum\Typo3ConsolePlugin\Config as PluginConfig;
use TYPO3\CMS\Core\Package\PackageInterface;

class GeneratePackageStates implements InstallerScriptInterface
{
    /**
     * @param ScriptEvent $event
     * @return bool
     */
    public function shouldRun(ScriptEvent $event)
    {
        return getenv('TYPO3_CONSOLE_TEST_SETUP') || $event->getComposer()->getPackage()->getName() !== 'helhum/typo3-console';
    }

    /**
     * @param ScriptEvent $event
     * @return bool
     * @throws \RuntimeException
     * @internal
     */
    public function run(ScriptEvent $event)
    {
        $io = $event->getIO();
        $pluginConfig = PluginConfig::load($io, $event->getComposer()->getConfig());

        if ($pluginConfig->get('skip-packagestates-write')) {
            $io->writeError('<warning>It is highly recommended to let the PackageStates.php file be generated automatically</warning>');
            $io->writeError('<warning>Disabling this functionality will be removed with TYPO3 Console 5.0</warning>');
            return;
        }
        $bootstrap = self::ensureTypo3Booted();
        $packageManager = $bootstrap->getEarlyInstance(\TYPO3\CMS\Core\Package\PackageManager::class);
        $packageStateGenerator = new PackageStatesGenerator($packageManager);

        $frameworkExtensions = explode(',', (string)getenv('TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS'));
        $activateDefault = (bool)getenv('TYPO3_ACTIVATE_DEFAULT_FRAMEWORK_EXTENSIONS');
        $excludedExtensions = $event->isDevMode() ? explode(',', (string)getenv('TYPO3_EXCLUDED_EXTENSIONS')) : [];

        $activatedExtensions = $packageStateGenerator->generate($frameworkExtensions, $activateDefault, $excludedExtensions);

        $io->writeError(
            sprintf(
                '<info>The following extensions have been added to the generated PackageStates.php file:</info> %s',
                implode(', ', array_map(function (PackageInterface $package) {
                    return $package->getPackageKey();
                }, $activatedExtensions))
            ),
            true,
            $io::VERBOSE
        );
        if ($event->isDevMode() && !empty(getenv('TYPO3_EXCLUDED_EXTENSIONS'))) {
            $io->writeError(
                sprintf(
                    '<info>The following third party extensions were excluded during this process:</info> %s',
                    getenv('TYPO3_EXCLUDED_EXTENSIONS')
                ),
                true,
                $io::VERBOSE
            );
        }
        return true;
    }

    /**
     * @return bool
     */
    private static function hasTypo3Booted()
    {
        // Since this code is executed in composer runtime,
        // we can safely assume that TYPO3 has not been bootstrapped
        // until this API has been initialized to return true
        return ConsoleBootstrap::usesComposerClassLoading();
    }

    /**
     * @return ConsoleBootstrap
     */
    private static function ensureTypo3Booted()
    {
        if (!self::hasTypo3Booted()) {
            define('PATH_site', getenv('TYPO3_PATH_WEB') . '/');
            $bootstrap = ConsoleBootstrap::create('Production');
            $bootstrap->initialize(new \Composer\Autoload\ClassLoader());
        } else {
            $bootstrap = ConsoleBootstrap::getInstance();
        }
        return $bootstrap;
    }
}
