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
     * Skip reading these commands, as we provide
     * better compatible versions on our own
     *
     * @var array
     */
    private static $typo3CommandsToIgnore = [
        'extbase',
        '_extbase_help',
        '_core_command',
    ];

    /**
     * @var BaseCommand[]
     */
    private $commands;

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
        return $this->populateFromCommandControllers(true);
    }

    private function populateCommands()
    {
        if ($this->commands) {
            return;
        }
        $this->registerCommandsFromCommandControllers();
        $this->populateFromCommandControllers();
        $this->populateFromConfigurationFiles();
    }

    /**
     * @param bool $onlyNew
     * @throws CommandNameAlreadyInUseException
     * @return BaseCommand[]
     */
    private function populateFromCommandControllers($onlyNew = false): array
    {
        $registeredCommands = [];
        foreach ($this->commandManager->getAvailableCommands($onlyNew) as $commandDefinition) {
            $commandName = $this->commandManager->getShortestIdentifierForCommand($commandDefinition);
            $fullCommandName = $commandDefinition->getCommandIdentifier();
            if ($fullCommandName === 'typo3_console:help:help') {
                continue;
            }
            if (isset($this->commands[$commandName])) {
                $commandName = $fullCommandName;
            }
            if (isset($this->commands[$commandName])) {
                throw new CommandNameAlreadyInUseException('Command "' . $commandName . '" registered by "' . explode(':', $fullCommandName)[0] . '" is already in use', 1484486383);
            }
            $extbaseCommand = GeneralUtility::makeInstance(CommandControllerCommand::class, $commandName, $commandDefinition);
            $registeredCommands[$commandName] = $this->commands[$commandName] = $extbaseCommand;
        }

        return $registeredCommands;
    }

    /**
     * Finds all command controller and Symfony commands and populates the registry
     *
     * @throws CommandNameAlreadyInUseException
     */
    private function populateFromConfigurationFiles()
    {
        foreach ($this->getCommandConfigurations() as $packageName => $configuration) {
            if (empty($configuration['commands'])) {
                continue;
            }
            foreach ($configuration['commands'] as $commandName => $commandConfig) {
                if (in_array($commandName, self::$typo3CommandsToIgnore, true)) {
                    continue;
                }
                if (isset($this->commands[$commandName])) {
                    $commandName = $packageName . ':' . $commandName;
                }
                if (isset($this->commands[$commandName])) {
                    throw new CommandNameAlreadyInUseException(
                        'Command "' . $commandName . '" registered by "' . $package->getPackageKey() . '" is already in use',
                        1506442316
                    );
                }
                $command = GeneralUtility::makeInstance($commandConfig['class'], $commandName);
                $this->commands[$commandName] = $command;
            }
        }
    }

    private function registerCommandsFromCommandControllers()
    {
        foreach ($this->getCommandConfigurations() as $packageKey => $commandConfiguration) {
            $this->registerCommandsFromConfiguration($commandConfiguration, $packageKey);
        }
    }

    /**
     * @return array
     */
    private function getCommandConfigurations()
    {
        if (file_exists($commandConfigurationFile = __DIR__ . '/../../../Configuration/Console/AllCommands.php')) {
            return require $commandConfigurationFile;
        }
        $commandConfigurationFiles = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            $installPath = $package->getPackagePath();
            $packageConfig = $this->getConfigFromPackage($installPath);
            if (!empty($packageConfig)) {
                $commandConfiguration[$package->getPackageKey()] = $packageConfig;
            }
        }
        return $commandConfigurationFiles;
    }

    /**
     * @param $installPath
     * @return mixed
     */
    private function getConfigFromPackage($installPath)
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
     * @param $commandConfiguration
     * @param $packageKey
     */
    private function registerCommandsFromConfiguration($commandConfiguration, $packageKey)
    {
        $this->ensureValidCommandsConfiguration($commandConfiguration, $packageKey);

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
        ) {
            throw new \RuntimeException($packageKey . ' defines invalid commands in Configuration/Console/Commands.php', 1461186959);
        }
    }
}
