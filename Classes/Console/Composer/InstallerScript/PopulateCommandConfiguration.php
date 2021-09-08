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

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Script\Event as ScriptEvent;
use Composer\Util\PackageSorter;
use Symfony\Component\Console\Exception\RuntimeException;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScript;

/**
 * Reads console command configuration files from all composer packages in the current project
 * and writes a file with all command configurations accumulated
 */
class PopulateCommandConfiguration implements InstallerScript
{
    /**
     * @var IOInterface
     */
    private $io;

    /**
     * Called from Composer
     *
     * @param ScriptEvent $event
     * @return bool
     * @internal
     */
    public function run(ScriptEvent $event): bool
    {
        $this->io = $event->getIO();
        $composer = $event->getComposer();
        $composerConfig = $composer->getConfig();
        $basePath = realpath(substr($composerConfig->get('vendor-dir'), 0, -strlen($composerConfig->get('vendor-dir', $composerConfig::RELATIVE_PATHS))));
        $commandConfiguration = [];
        foreach ($this->extractPackageMapFromComposer($composer) as [$package, $installPath]) {
            /** @var PackageInterface $package */
            $installPath = ($installPath ?: $basePath);
            if (in_array($package->getType(), ['metapackage', 'typo3-cms-extension', 'typo3-cms-framework'], true)) {
                // Commands in TYPO3 extensions are not scanned any more. They should rather use DI configuration to register commands.
                // Since meta packages have no code, thus cannot include any commands, we ignore them as well.
                continue;
            }
            $packageConfig = $this->getConfigFromPackage($installPath, $package);
            if ($packageConfig !== []) {
                $commandConfiguration[] = $packageConfig;
            }
        }
        $generatedConfigFilePath = $composerConfig->get('vendor-dir') . '/helhum/typo3-console/Configuration/ComposerPackagesCommands.php';
        if ($composer->getPackage()->getName() === 'helhum/typo3-console') {
            $generatedConfigFilePath = $basePath . '/Configuration/ComposerPackagesCommands.php';
        }

        $success = file_put_contents(
            $generatedConfigFilePath,
            '<?php' . chr(10)
            . 'return '
            . var_export(array_merge([], ...$commandConfiguration), true)
            . ';'
        );

        return $success !== false;
    }

    /**
     * @param Composer $composer
     * @return array
     */
    private function extractPackageMapFromComposer(Composer $composer): array
    {
        $mainPackage = $composer->getPackage();
        $autoLoadGenerator = $composer->getAutoloadGenerator();
        $localRepo = $composer->getRepositoryManager()->getLocalRepository();

        return $this->sortPackageMap($autoLoadGenerator->buildPackageMap($composer->getInstallationManager(), $mainPackage, $localRepo->getCanonicalPackages()));
    }

    /**
     * Sorts packages by dependency weight
     *
     * Packages of equal weight retain the original order
     *
     * @param  array $packageMap
     * @return array
     */
    private function sortPackageMap(array $packageMap): array
    {
        $packages = [];
        $paths = [];

        foreach ($packageMap as [$package, $path]) {
            /** @var PackageInterface $package */
            $name = $package->getName();
            $packages[$name] = $package;
            $paths[$name] = $path;
        }

        $sortedPackages = PackageSorter::sortPackages($packages);

        $sortedPackageMap = [];
        $sortedPackageMap[] = [$packages['helhum/typo3-console'], $paths['helhum/typo3-console']];

        foreach ($sortedPackages as $package) {
            $name = $package->getName();
            if ($name === 'helhum/typo3-console') {
                continue;
            }
            $sortedPackageMap[] = [$packages[$name], $paths[$name]];
        }

        return $sortedPackageMap;
    }

    private function getConfigFromPackage(string $installPath, PackageInterface $package): array
    {
        if (!file_exists($commandConfigurationFile = $installPath . '/Configuration/Commands.php')) {
            return [];
        }

        if (empty($commandConfiguration = require $commandConfigurationFile)) {
            return [];
        }
        $this->ensureValidCommandRegistration($commandConfiguration, $this->resolveVendorName($package));

        return $this->unifyCommandConfiguration($commandConfiguration, $package);
    }

    /**
     * @param mixed $commandConfiguration
     * @param string $packageName
     * @throws RuntimeException
     */
    private function ensureValidCommandRegistration($commandConfiguration, $packageName): void
    {
        if (!is_array($commandConfiguration)) {
            throw new RuntimeException($packageName . ' defines invalid commands in Configuration/Console/Commands.php', 1461186959);
        }
    }

    private function unifyCommandConfiguration(array $commandConfiguration, PackageInterface $package): array
    {
        $commandDefinitions = [];

        foreach ($commandConfiguration ?? [] as $commandName => $commandConfig) {
            $vendor = $commandConfig['vendor'] ?? $this->resolveVendorName($package);
            $nameSpacedCommandName = $vendor . ':' . $commandName;
            $commandConfig['vendor'] = $vendor;
            $commandConfig['name'] = $commandName;
            $commandConfig['nameSpacedName'] = $nameSpacedCommandName;
            $commandConfig['service'] = false;
            $commandDefinitions[] = $commandConfig;
        }

        return $commandDefinitions;
    }

    private function resolveVendorName(PackageInterface $package): string
    {
        $vendor = $package->getName();
        if (strpos($package->getType(), 'typo3-cms-') === 0) {
            $vendor = $package->getExtra()['typo3/cms']['extension-key'] ?? $vendor;
        }

        return $vendor;
    }
}
