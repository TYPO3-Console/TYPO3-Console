<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Extension;

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

use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;

class ExtensionCompatibilityCheck
{
    /**
     * @var PackageManager
     */
    private $packageManager;

    /**
     * @var CommandDispatcher
     */
    private $commandDispatcher;

    public function __construct(PackageManager $packageManager, CommandDispatcher $commandDispatcher)
    {
        $this->packageManager = $packageManager;
        $this->commandDispatcher = $commandDispatcher;
    }

    /**
     * This method is meant to be called form a sub process
     * specifically from upgrade:checkextensioncompatiblity command
     *
     * @param string $extensionKey
     * @param bool $configOnly
     * @return bool
     */
    public function isCompatible($extensionKey, $configOnly = false)
    {
        try {
            if ($configOnly) {
                return $this->canLoadExtLocalconfFile($extensionKey);
            }

            return $this->canLoadExtTablesFile($extensionKey);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array
     */
    public function findIncompatible()
    {
        $failedPackages = [];
        $activePackages = $this->packageManager->getActivePackages();
        foreach ($activePackages as $package) {
            if (strpos($package->getPackagePath(), '/typo3conf/ext/') === false) {
                // There is not need to check core extensions
                continue;
            }
            $isCompatible = @\json_decode($this->commandDispatcher->executeCommand('upgrade:checkextensioncompatibility', [$package->getPackageKey(), '--config-only']));
            if (!$isCompatible) {
                !Environment::isComposerMode() && $this->packageManager->deactivatePackage($package->getPackageKey());
                $failedPackages[] = $package->getPackageKey();
                continue;
            }
            $isCompatible = @\json_decode($this->commandDispatcher->executeCommand('upgrade:checkextensioncompatibility', [$package->getPackageKey()]));
            if (!$isCompatible) {
                !Environment::isComposerMode() && $this->packageManager->deactivatePackage($package->getPackageKey());
                $failedPackages[] = $package->getPackageKey();
            }
        }

        return $failedPackages;
    }

    /**
     * Load all ext_localconf files in order until given extension key
     *
     * @param string $extensionKey
     * @return bool
     */
    private function canLoadExtLocalconfFile($extensionKey)
    {
        $activePackages = $this->packageManager->getActivePackages();
        foreach ($activePackages as $package) {
            $this->loadExtLocalconfForExtension($package);
            if ($package->getPackageKey() === $extensionKey) {
                break;
            }
        }

        return true;
    }

    /**
     * Load all ext_table files in order until given extension key
     *
     * @param string $extensionKey
     * @return bool
     */
    private function canLoadExtTablesFile($extensionKey)
    {
        $activePackages = $this->packageManager->getActivePackages();
        foreach ($activePackages as $package) {
            // Load all ext_localconf files first
            $this->loadExtLocalconfForExtension($package);
        }
        foreach ($activePackages as $package) {
            $this->loadExtTablesForExtension($package);
            if ($package->getPackageKey() === $extensionKey) {
                break;
            }
        }

        return true;
    }

    /**
     * Loads ext_localconf.php for a single extension. Method is a modified copy of
     * the original bootstrap method.
     *
     * @param PackageInterface $package
     */
    private function loadExtLocalconfForExtension(PackageInterface $package)
    {
        $extLocalconfPath = $package->getPackagePath() . 'ext_localconf.php';
        if (@file_exists($extLocalconfPath)) {
            require $extLocalconfPath;
        }
    }

    /**
     * Loads ext_tables.php for a single extension. Method is a modified copy of
     * the original bootstrap method.
     *
     * @param PackageInterface $package
     */
    private function loadExtTablesForExtension(PackageInterface $package)
    {
        $extTablesPath = $package->getPackagePath() . 'ext_tables.php';
        if (@file_exists($extTablesPath)) {
            require $extTablesPath;
        }
    }
}
