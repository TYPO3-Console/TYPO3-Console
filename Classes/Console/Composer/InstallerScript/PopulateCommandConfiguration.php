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
use Helhum\Typo3Console\Mvc\Cli\CommandConfiguration;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScript;

/**
 * Reads console command configuration files from all composer packages in the current project
 * and writes a file with all command configurations accumulated
 */
class PopulateCommandConfiguration implements InstallerScript
{
    /**
     * Called from Composer
     *
     * @param ScriptEvent $event
     * @return bool
     * @internal
     */
    public function run(ScriptEvent $event): bool
    {
        $composer = $event->getComposer();
        $composerConfig = $composer->getConfig();
        $basePath = realpath(substr($composerConfig->get('vendor-dir'), 0, -strlen($composerConfig->get('vendor-dir', $composerConfig::RELATIVE_PATHS))));
        $commandConfiguration = [];
        foreach ($this->extractPackageMapFromComposer($composer) as $item) {
            /** @var \Composer\Package\PackageInterface $package */
            list($package, $installPath) = $item;
            $installPath = ($installPath ?: $basePath);
            $packageName = $package->getName();
            $packageType = $package->getType();
            if (in_array($packageType, ['metapackage', 'typo3-cms-extension', 'typo3-cms-framework'], true)) {
                // Commands in TYPO3 extensions are scanned for anyway at a later point.
                // With that we ensure not showing commands for extensions that aren't active.
                // Since meta packages have no code, thus cannot include any commands, we ignore them as well.
                continue;
            }
            $commandConfiguration = array_merge($commandConfiguration, $this->getConfigFromPackage($installPath, $packageName));
        }

        $success = file_put_contents(
            __DIR__ . '/../../../../Configuration/Console/ComposerPackagesCommands.php',
            '<?php' . chr(10)
            . 'return '
            . var_export($commandConfiguration, true)
            . ';'
        );

        return $success !== false;
    }

    /**
     * @param \Composer\Composer $composer
     * @return array
     */
    private function extractPackageMapFromComposer(\Composer\Composer $composer): array
    {
        $mainPackage = $composer->getPackage();
        $autoLoadGenerator = $composer->getAutoloadGenerator();
        $localRepo = $composer->getRepositoryManager()->getLocalRepository();

        return $autoLoadGenerator->buildPackageMap($composer->getInstallationManager(), $mainPackage, $localRepo->getCanonicalPackages());
    }

    /**
     * @param string $installPath
     * @param string $packageName
     * @throws \Symfony\Component\Console\Exception\RuntimeException
     * @return array
     */
    private function getConfigFromPackage(string $installPath, string $packageName): array
    {
        $commandConfiguration = [];
        if (file_exists($commandConfigurationFile = $installPath . '/Configuration/Console/Commands.php')) {
            $commandConfiguration = require $commandConfigurationFile;
        }
        if (file_exists($commandConfigurationFile = $installPath . '/Configuration/Commands.php')) {
            $commandConfiguration['commands'] = require $commandConfigurationFile;
        }
        if (empty($commandConfiguration)) {
            return [];
        }
        CommandConfiguration::ensureValidCommandRegistration($commandConfiguration, $packageName);

        return [$packageName => CommandConfiguration::unifyCommandConfiguration($commandConfiguration, $packageName)];
    }
}
