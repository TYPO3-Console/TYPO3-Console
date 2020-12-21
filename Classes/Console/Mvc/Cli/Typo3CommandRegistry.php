<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Mvc\Cli;

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

use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Overrides the TYPO3 command registry to only contain the commands defined as services
 */
class Typo3CommandRegistry extends CommandRegistry
{
    public function __construct(CommandRegistry $commandRegistry)
    {
        parent::__construct($commandRegistry->container);
        $this->commandConfigurations = $commandRegistry->commandConfigurations;
    }

    public function getServiceConfiguration(): array
    {
        return $this->commandConfigurations;
    }

    public function getCommandConfiguration(): array
    {
        // TODO: Fixme! This should be an empty array in 11, needs further conceptual steps
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $commandConfigurations = [];
        foreach ($packageManager->getActivePackages() as $package) {
            $commandsOfExtension = $package->getPackagePath() . 'Configuration/Commands.php';
            if (!file_exists($commandsOfExtension)) {
                continue;
            }
            if (!is_array($commands = require $commandsOfExtension)) {
                continue;
            }
            $vendor = $package->getPackageKey();
            foreach ($commands as $commandName => $commandConfig) {
                if (!empty($this->commandConfigurations[$commandName])
                    && $commandConfig['class'] === $this->commandConfigurations[$commandName]['serviceName']
                ) {
                    // Lazy (DI managed) commands override classic commands from Configuration/Commands.php
                    // Skip this case to allow extensions to provide commands via DI config and to allow
                    // TYPO3 v9 backwards compatible configuration via Configuration/Commands.php.
                    // Note: Also the deprecation error is skipped on-demand as the extension has been
                    // adapted and the configuration will be ignored as of TYPO3 v11.
                    continue;
                }
                $commandConfig['vendor'] = $commandConfig['vendor'] ?? $vendor;
                $commandConfig['name'] = $commandName;
                $commandConfig['nameSpacedName'] = $commandConfig['vendor'] . ':' . $commandName;
                $commandConfig['service'] = false;
                $commandConfigurations[] = $commandConfig;

                if (!isset($commandConfig['runLevel'])) {
                    trigger_error(
                        'Registering console commands in Configuration/Commands.php has been deprecated and will stop working in TYPO3 v11.0.',
                        E_USER_DEPRECATED
                    );
                }
            }
        }

        return $commandConfigurations;
    }
}
