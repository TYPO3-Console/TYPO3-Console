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
     * @throws \RuntimeException
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
            if (file_exists($commandConfigurationFile = $installPath . '/Configuration/Console/Commands.php')) {
                $commandConfiguration[$package->getName()] = require $commandConfigurationFile;
            }
        }
        $success = file_put_contents(
            __DIR__ . '/../../../Configuration/Console/AllCommands.php',
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
    private function extractPackageMapFromComposer(\Composer\Composer $composer)
    {
        $mainPackage = $composer->getPackage();
        $autoLoadGenerator = $composer->getAutoloadGenerator();
        $localRepo = $composer->getRepositoryManager()->getLocalRepository();
        return $autoLoadGenerator->buildPackageMap($composer->getInstallationManager(), $mainPackage, $localRepo->getCanonicalPackages());
    }
}
