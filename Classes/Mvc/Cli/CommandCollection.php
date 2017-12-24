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

use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Command\CommandControllerCommand;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Exception\RuntimeException;
use TYPO3\CMS\Core\Console\CommandNameAlreadyInUseException;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\CommandManager;

/**
 * Represents a collection of commands
 *
 * This implementation pulls in the commands from various places,
 * mainly reading configuration files from TYPO3 extensions and composer packages
 */
class CommandCollection implements \IteratorAggregate
{
    /**
     * @var BaseCommand[]
     */
    private $commands;

    /**
     * @var array
     */
    private $commandControllerClasses = [];

    /**
     * @var array
     */
    private $commandConfigurations = [];

    /**
     * @var array
     */
    private $replaces = [];

    /**
     * @var RunLevel
     */
    private $runLevel;

    /**
     * @var PackageManager
     */
    private $packageManager;

    public function __construct(RunLevel $runLevel, PackageManager $packageManager = null)
    {
        $this->runLevel = $runLevel;
        $this->packageManager = $packageManager;
    }

    /**
     * @return \Generator
     */
    public function getIterator(): \Generator
    {
        $this->populateCommands();
        foreach ($this->commands as $commandName => $command) {
            yield $commandName => $command;
        }
    }

    /**
     * Add command controller commands
     *
     * @throws \TYPO3\CMS\Core\Console\CommandNameAlreadyInUseException
     * @return BaseCommand[]
     */
    public function addCommandControllerCommands(CommandManager $commandManager): array
    {
        $this->populateCommands();
        return $this->populateCommandControllerCommands($commandManager);
    }

    private function populateCommands()
    {
        if ($this->commands !== null) {
            return;
        }
        $this->commands = [];
        $this->initializeConfiguration();
        $this->populateNativeCommands();
    }

    private function initializeConfiguration()
    {
        foreach ($this->getCommandConfigurations() as $packageKey => $commandConfiguration) {
            $this->ensureValidCommandsConfiguration($commandConfiguration, $packageKey);
            if (isset($commandConfiguration['replace'])) {
                $this->replaces = array_merge($this->replaces, $commandConfiguration['replace']);
            }
            if (isset($commandConfiguration['controllers'])) {
                $this->commandControllerClasses = array_merge($this->commandControllerClasses, $commandConfiguration['controllers']);
            }
            if (isset($commandConfiguration['runLevels'])) {
                foreach ($commandConfiguration['runLevels'] as $commandIdentifier => $runLevel) {
                    $this->runLevel->setRunLevelForCommand($commandIdentifier, $runLevel);
                }
            }
            if (isset($commandConfiguration['bootingSteps'])) {
                foreach ($commandConfiguration['bootingSteps'] as $commandIdentifier => $bootingSteps) {
                    foreach ((array)$bootingSteps as $bootingStep) {
                        $this->runLevel->addBootingStepForCommand($commandIdentifier, $bootingStep);
                    }
                }
            }
        }
    }

    /**
     * @param CommandManager $commandManager
     * @return array
     */
    private function populateCommandControllerCommands(CommandManager $commandManager): array
    {
        $registeredCommands = [];
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] ?? [];
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] = array_merge($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'], $this->commandControllerClasses);
        foreach ($commandManager->getAvailableCommands() as $commandDefinition) {
            $fullCommandIdentifier = $commandDefinition->getCommandIdentifier();
            $baseCommandIdentifier = explode(':', $fullCommandIdentifier, 2)[1];
            $commandName = $this->getFinalCommandName(
                $baseCommandIdentifier,
                $fullCommandIdentifier
            );
            if ($commandName) {
                $commandControllerCommand = GeneralUtility::makeInstance(CommandControllerCommand::class, $commandName, $commandDefinition);
                $this->add($commandControllerCommand, $fullCommandIdentifier);
                $registeredCommands[] = $commandControllerCommand;
            }
        }

        return $registeredCommands;
    }

    /**
     * Finds all command controller and Symfony commands and populates the registry
     *
     * @throws CommandNameAlreadyInUseException
     */
    private function populateNativeCommands()
    {
        foreach ($this->getCommandConfigurations() as $packageName => $configuration) {
            if (empty($configuration['commands'])) {
                continue;
            }
            foreach ($configuration['commands'] as $baseCommandIdentifier => $commandConfig) {
                $fullCommandIdentifier = $packageName . ':' . $baseCommandIdentifier;
                $commandName = $this->getFinalCommandName(
                    $baseCommandIdentifier,
                    $fullCommandIdentifier
                );
                if ($commandName) {
                    /** @var BaseCommand $command */
                    $command = GeneralUtility::makeInstance($commandConfig['class'], $commandName);
                    // No aliases for native commands (they can define their own aliases)
                    $this->add($command, $commandName);
                }
            }
        }
    }

    private function add(BaseCommand $command, $fullCommandIdentifier)
    {
        $commandName = $command->getName();
        $this->commands[$commandName] = $command;
        if ($commandName !== $fullCommandIdentifier) {
            // @deprecated in 5.0 will be removed in 6.0
            $this->commands[$commandName]->setAliases([$fullCommandIdentifier]);
        }
    }

    private function getFinalCommandName(string $commandName, string $alternativeCommandName): string
    {
        $finalCommandName = $commandName;
        if (in_array($commandName, $this->replaces, true)
            || in_array($alternativeCommandName, $this->replaces, true)
        ) {
            return '';
        }
        if (isset($this->commands[$commandName])) {
            $finalCommandName = $alternativeCommandName;
        }
        if (isset($this->commands[$finalCommandName])) {
            throw new CommandNameAlreadyInUseException('Command "' . $finalCommandName . '" registered by "' . explode(':', $alternativeCommandName)[0] . '" is already in use', 1506531326);
        }
        return $finalCommandName;
    }

    /**
     * @return array
     */
    private function getCommandConfigurations(): array
    {
        if (!empty($this->commandConfigurations)) {
            return $this->commandConfigurations;
        }
        if (file_exists($commandConfigurationFile = __DIR__ . '/../../../Configuration/Console/AllCommands.php')) {
            return require $commandConfigurationFile;
        }

        // All code below is only for non composer mode
        if (!$this->packageManager->isPackageAvailable('typo3_console')
            || !$this->packageManager->isPackageActive('typo3_console')
        ) {
            // We are not installed, or not even existing as extension, but we still want to be able to run,
            // thus we include our config directly in this case.
            $this->commandConfigurations['typo3_console'] = require __DIR__ . '/../../../Configuration/Console/Commands.php';
        }
        foreach ($this->packageManager->getActivePackages() as $package) {
            $installPath = $package->getPackagePath();
            $packageConfig = $this->getConfigFromPackage($installPath);
            if (!empty($packageConfig)) {
                $this->commandConfigurations[$package->getPackageKey()] = $packageConfig;
            }
        }
        return $this->commandConfigurations;
    }

    private function getConfigFromPackage(string $installPath): array
    {
        $commandConfiguration = [];
        if (file_exists($commandConfigurationFile = $installPath . '/Configuration/Console/Commands.php')) {
            $commandConfiguration = require $commandConfigurationFile;
        }
        if (file_exists($commandConfigurationFile = $installPath . '/Configuration/Commands.php')) {
            $commandConfiguration['commands'] = require $commandConfigurationFile;
        }
        return $commandConfiguration;
    }

    /**
     * @param mixed $commandConfiguration
     * @param string $packageKey
     * @throws RuntimeException
     */
    private function ensureValidCommandsConfiguration($commandConfiguration, $packageKey)
    {
        if (
            !is_array($commandConfiguration)
            || (isset($commandConfiguration['controllers']) && !is_array($commandConfiguration['controllers']))
            || (isset($commandConfiguration['runLevels']) && !is_array($commandConfiguration['runLevels']))
            || (isset($commandConfiguration['bootingSteps']) && !is_array($commandConfiguration['bootingSteps']))
            || (isset($commandConfiguration['commands']) && !is_array($commandConfiguration['commands']))
            || (isset($commandConfiguration['replace']) && !is_array($commandConfiguration['replace']))
        ) {
            throw new RuntimeException($packageKey . ' defines invalid commands in Configuration/Console/Commands.php', 1461186959);
        }
    }
}
