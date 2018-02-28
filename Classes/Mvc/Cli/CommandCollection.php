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
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;
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
class CommandCollection implements CommandLoaderInterface
{
    /**
     * Only use for rendering the reference
     *
     * @var bool
     * @internal
     */
    public static $rendersReference = false;

    /**
     * @var BaseCommand[]
     */
    private $commands;

    /**
     * @var string[]
     */
    private $aliases;

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

    public function __construct(RunLevel $runLevel, PackageManager $packageManager)
    {
        $this->runLevel = $runLevel;
        $this->packageManager = $packageManager;
        $this->populateCommands();
    }

    /**
     * @param string $name
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     * @return BaseCommand
     */
    public function get($name): BaseCommand
    {
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }
        if (!isset($this->commands[$name]) || !$this->isCommandAvailable($name)) {
            throw new CommandNotFoundException(sprintf('The command "%s" does not exist.', $name), [], 1518812618);
        }
        $command = $this->commands[$name]['closure']();
        if (isset($this->commands[$name]['aliases'])) {
            $command->setAliases($this->commands[$name]['aliases']);
        }
        return $command;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name): bool
    {
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }
        return isset($this->commands[$name]) && $this->isCommandAvailable($name);
    }

    /**
     * @return string[]
     */
    public function getNames(): array
    {
        return array_merge(array_keys($this->commands), array_keys($this->aliases));
    }

    /**
     * Add command controller commands
     *
     * @throws \TYPO3\CMS\Core\Console\CommandNameAlreadyInUseException
     */
    public function addCommandControllerCommands(CommandManager $commandManager)
    {
        $this->populateCommands();
        $this->populateCommandControllerCommands($commandManager);
    }

    private function isCommandAvailable(string $name): bool
    {
        if (self::$rendersReference) {
            return true;
        }
        return $this->runLevel->isCommandAvailable($name);
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
     */
    private function populateCommandControllerCommands(CommandManager $commandManager)
    {
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
                $closure = function () use ($commandName, $commandDefinition) {
                    return GeneralUtility::makeInstance(CommandControllerCommand::class, $commandName, $commandDefinition);
                };
                $aliases = [];
                if ($commandName !== $fullCommandIdentifier) {
                    // @deprecated in 5.0 will be removed in 6.0
                    $aliases = [$fullCommandIdentifier];
                }
                $this->add($closure, $commandName, $aliases);
            }
        }
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
                    $closure = function () use ($commandConfig, $commandName) {
                        /** @var BaseCommand $command */
                        return GeneralUtility::makeInstance($commandConfig['class'], $commandName);
                    };
                    $this->add($closure, $commandName, $commandConfig['aliases'] ?? []);
                }
            }
        }
    }

    private function add(\Closure $closure, $commandName, array $aliases = [])
    {
        $this->commands[$commandName]['closure'] = $closure;
        foreach ($aliases as $alias) {
            $this->aliases[$alias] = $commandName;
            $this->commands[$commandName]['aliases'][] = $alias;
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
        if (file_exists($commandConfigurationFile = __DIR__ . '/../../../Configuration/Console/ComposerPackagesCommands.php')) {
            $this->commandConfigurations = require $commandConfigurationFile;
        } else {
            // We only reach this point in non composer mode
            // We ensure that our commands are present, even if we are not an active extension or even not being an extension at all
            $this->commandConfigurations['typo3_console'] = require __DIR__ . '/../../../Configuration/Console/Commands.php';
        }
        $this->populateCommandConfigurationFromExtensions();
        return $this->commandConfigurations;
    }

    private function populateCommandConfigurationFromExtensions()
    {
        foreach ($this->packageManager->getActivePackages() as $package) {
            $packageConfig = $this->getConfigFromExtension($package->getPackagePath());
            if (!empty($packageConfig)) {
                $this->commandConfigurations[$package->getPackageKey()] = $packageConfig;
            }
        }
    }

    private function getConfigFromExtension(string $installPath): array
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
