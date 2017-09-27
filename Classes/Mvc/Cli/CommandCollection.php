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
use TYPO3\CMS\Core\Console\CommandNameAlreadyInUseException;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

    /**
     * @var CommandManager
     */
    private $commandManager;

    public function __construct(RunLevel $runLevel, PackageManager $packageManager, CommandManager $commandManager)
    {
        $this->runLevel = $runLevel;
        $this->packageManager = $packageManager;
        $this->commandManager = $commandManager;
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
     * Add commands that are registered in TYPO3_CONF_VARS
     *
     * Needs to be called after ext_localconf.php files from extensions have been loaded
     *
     * @throws \TYPO3\CMS\Core\Console\CommandNameAlreadyInUseException
     * @return BaseCommand[]
     */
    public function addCommandControllerCommandsFromExtensions()
    {
        $this->populateCommands();
        return $this->populateCommandControllerCommands(true);
    }

    private function populateCommands()
    {
        if ($this->commands) {
            return;
        }
        $this->initializeConfiguration();
        $this->populateCommandControllerCommands();
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
                foreach ($commandConfiguration['controllers'] as $controller) {
                    $this->commandManager->registerCommandController($controller);
                }
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
     * @param bool $onlyNew
     * @throws CommandNameAlreadyInUseException
     * @return BaseCommand[]
     */
    private function populateCommandControllerCommands($onlyNew = false): array
    {
        $registeredCommands = [];
        foreach ($this->commandManager->getAvailableCommands($onlyNew) as $commandDefinition) {
            $commandIdentifier = $commandDefinition->getCommandIdentifier();
            $shortIdentifier = explode(':', $commandIdentifier, 2)[1];
            $commandName = $this->getFinalCommandName(
                $shortIdentifier,
                $commandIdentifier
            );
            if ($commandName) {
                $extbaseCommand = GeneralUtility::makeInstance(CommandControllerCommand::class, $commandName, $commandDefinition);
                $this->commands[$commandName] = $registeredCommands[$commandName] = $extbaseCommand;
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
            foreach ($configuration['commands'] as $commandName => $commandConfig) {
                $commandName = $this->getFinalCommandName(
                    $commandName,
                    $packageName . ':' . $commandName
                );
                if ($commandName) {
                    $command = GeneralUtility::makeInstance($commandConfig['class'], $commandName);
                    $this->commands[$commandName] = $command;
                }
            }
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
     * @throws \RuntimeException
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
            throw new \RuntimeException($packageKey . ' defines invalid commands in Configuration/Console/Commands.php', 1461186959);
        }
    }
}
