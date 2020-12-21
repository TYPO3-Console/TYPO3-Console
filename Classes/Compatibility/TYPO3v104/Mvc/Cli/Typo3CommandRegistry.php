<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\TYPO3v104\Mvc\Cli;

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

/**
 * Overrides the TYPO3 command registry to only contain the commands defined as services
 * @deprecated can be removed once TYPO3 10.4 compat is removed
 */
class Typo3CommandRegistry extends CommandRegistry
{
    public function __construct(CommandRegistry $commandRegistry)
    {
        parent::__construct($commandRegistry->packageManager, $commandRegistry->container);
        $this->lazyCommandConfigurations = $commandRegistry->lazyCommandConfigurations;
    }

    protected function populateCommandsFromPackages()
    {
        if ($this->commands) {
            return;
        }

        foreach ($this->lazyCommandConfigurations as $commandName => $commandConfig) {
            $this->commands[$commandName] = $commandConfig['class'];
        }
        $this->populateCommandsFromExtensions();
    }

    private function populateCommandsFromExtensions(): void
    {
        foreach ($this->packageManager->getActivePackages() as $package) {
            $commandsOfExtension = $package->getPackagePath() . 'Configuration/Commands.php';
            if (!file_exists($commandsOfExtension)) {
                continue;
            }
            if (!is_array($commands = require $commandsOfExtension)) {
                continue;
            }
            $vendor = $package->getPackageKey();
            foreach ($commands as $commandName => $commandConfig) {
                if (!empty($this->lazyCommandConfigurations[$commandName])
                    && $commandConfig['class'] === $this->lazyCommandConfigurations[$commandName]['class']
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
                $this->commandConfigurations[] = $commandConfig;

                if (!isset($commandConfig['runLevel'])) {
                    trigger_error(
                        'Registering console commands in Configuration/Commands.php has been deprecated and will stop working in TYPO3 v11.0.',
                        E_USER_DEPRECATED
                    );
                }
            }
        }
    }

    public function getServiceConfiguration(): array
    {
        $this->populateCommandsFromPackages();

        return $this->lazyCommandConfigurations;
    }

    public function getCommandConfiguration(): array
    {
        $this->populateCommandsFromPackages();

        return $this->commandConfigurations;
    }
}
