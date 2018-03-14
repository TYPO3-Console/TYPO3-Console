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
                $this->packageManager->deactivatePackage($package->getPackageKey());
                $failedPackages[] = $package->getPackageKey();
                continue;
            }
            $isCompatible = @\json_decode($this->commandDispatcher->executeCommand('upgrade:checkextensioncompatibility', [$package->getPackageKey()]));
            if (!$isCompatible) {
                $this->packageManager->deactivatePackage($package->getPackageKey());
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
            $this->loadExtLocalconfForExtension($package->getPackageKey());
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
            $this->loadExtLocalconfForExtension($package->getPackageKey());
        }
        foreach ($activePackages as $package) {
            $this->loadExtTablesForExtension($package->getPackageKey());
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
     * @param string $extensionKey
     */
    private function loadExtLocalconfForExtension($extensionKey)
    {
        $extensionInfo = $GLOBALS['TYPO3_LOADED_EXT'][$extensionKey];
        // This is the main array meant to be manipulated in the ext_localconf.php files
        // In general it is recommended to not rely on it to be globally defined in that
        // scope but to use $GLOBALS['TYPO3_CONF_VARS'] instead.
        // Nevertheless we define it here as global for backwards compatibility.
        global $TYPO3_CONF_VARS;
        $_EXTKEY = $extensionKey;
        if (isset($extensionInfo['ext_localconf.php']) && $extensionInfo['ext_localconf.php']) {
            // $_EXTKEY and $_EXTCONF are available in ext_localconf.php
            // and are explicitly set in cached file as well
            $_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY];
            require $extensionInfo['ext_localconf.php'];
        }
    }

    /**
     * Loads ext_tables.php for a single extension. Method is a modified copy of
     * the original bootstrap method.
     *
     * @param string $extensionKey
     */
    private function loadExtTablesForExtension($extensionKey)
    {
        $extensionInfo = $GLOBALS['TYPO3_LOADED_EXT'][$extensionKey];
        // In general it is recommended to not rely on it to be globally defined in that
        // scope, but we can not prohibit this without breaking backwards compatibility
        global $T3_SERVICES, $T3_VAR, $TYPO3_CONF_VARS;
        global $TBE_MODULES, $TBE_MODULES_EXT, $TCA;
        global $PAGES_TYPES, $TBE_STYLES;
        global $_EXTKEY;
        // Load each ext_tables.php file of loaded extensions
        $_EXTKEY = $extensionKey;
        if (isset($extensionInfo['ext_tables.php']) && $extensionInfo['ext_tables.php']) {
            // $_EXTKEY and $_EXTCONF are available in ext_tables.php
            // and are explicitly set in cached file as well
            $_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY];
            require $extensionInfo['ext_tables.php'];
        }
    }
}
