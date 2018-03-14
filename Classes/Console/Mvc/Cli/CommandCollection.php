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
use TYPO3\CMS\Core\Console\CommandNameAlreadyInUseException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Represents a collection of commands
 *
 * This implementation pulls in the commands from various places,
 * mainly reading configuration files from TYPO3 extensions and composer packages
 */
class CommandCollection implements CommandLoaderInterface
{
    /**
     * @var RunLevel
     */
    private $runLevel;

    /**
     * @var CommandConfiguration
     */
    private $commandConfiguration;

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
    private $commands = [];

    public function __construct(RunLevel $runLevel, CommandConfiguration $commandConfiguration)
    {
        $this->runLevel = $runLevel;
        $this->commandConfiguration = $commandConfiguration;
        $this->populateCommands();
        $this->initializeRunLevel();
    }

    /**
     * Try resolving short command names and aliases
     * If that fails, we return the command name as is and let the application throw an exception.
     *
     * Inspired by \Symfony\Component\Console\Application::find()
     *
     * @param string $possibleName
     * @return string
     */
    public function find(string $possibleName): string
    {
        $allCommands = $this->getNames();
        $expr = preg_replace_callback('{([^:]+|)}', function ($matches) {
            return preg_quote($matches[1], '/') . '[^:]*';
        }, $possibleName);
        $commands = preg_grep('{^' . $expr . '}', $allCommands);

        if (empty($commands)) {
            $commands = preg_grep('{^' . $expr . '}i', $allCommands);
        }

        // filter out aliases for commands which are already on the list
        if (count($commands) > 1) {
            $commandList = $this->commands;
            $commands = array_unique(array_filter($commands, function ($nameOrAlias) use ($commandList, $commands) {
                $commandName = $commandList[$nameOrAlias]['name'];

                return $commandName === $nameOrAlias || !in_array($commandName, $commands, true);
            }));
        }

        return !empty($commands) && count($commands) === 1 ? $this->commands[reset($commands)]['name'] : $possibleName;
    }

    /**
     * @param string $name
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     * @return BaseCommand
     */
    public function get($name): BaseCommand
    {
        if (!isset($this->commands[$name]) || !$this->isCommandAvailable($this->commands[$name]['name'])) {
            throw new CommandNotFoundException(sprintf('The command "%s" does not exist.', $name), [], 1518812618);
        }
        $commandConfig = $this->commands[$name];
        if (isset($commandConfig['class'])) {
            /** @var BaseCommand $command */
            $command = GeneralUtility::makeInstance($commandConfig['class'], $commandConfig['name']);
        } elseif (isset($commandConfig['controller'])) {
            $command = GeneralUtility::makeInstance(CommandControllerCommand::class, $commandConfig['name'], new Command($commandConfig['controller'], $commandConfig['controllerCommandName']));
        } else {
            throw new CommandNotFoundException(sprintf('The command "%s" does not exist.', $name), [], 1520205204);
        }

        if (!empty($this->commands[$name]['aliases'])) {
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
        return isset($this->commands[$name]) && $this->isCommandAvailable($this->commands[$name]['name']);
    }

    /**
     * @return string[]
     */
    public function getNames(): array
    {
        return array_keys($this->commands);
    }

    public function addCommandControllerCommands(array $commandControllers)
    {
        $this->populateCommands($this->commandConfiguration->addCommandControllerCommands($commandControllers));
    }

    private function isCommandAvailable(string $name): bool
    {
        if (self::$rendersReference) {
            return true;
        }

        return $this->runLevel->isCommandAvailable($name);
    }

    private function populateCommands(array $definitions = null)
    {
        foreach ($definitions ?? $this->commandConfiguration->getCommandDefinitions() as $nameSpacedName => $commandConfig) {
            $this->add($nameSpacedName, $commandConfig);
        }
    }

    private function initializeRunLevel()
    {
        foreach ($this->commands as $name => $commandConfig) {
            if (isset($commandConfig['runLevel'])) {
                $this->runLevel->setRunLevelForCommand($name, $commandConfig['runLevel']);
            }
            if (isset($commandConfig['bootingSteps'])) {
                foreach ($commandConfig['bootingSteps'] as $bootingStep) {
                    $this->runLevel->addBootingStepForCommand($name, $bootingStep);
                }
            }
        }
    }

    private function add(string $nameSpacedName, array $commandConfig)
    {
        $finalCommandName = $commandConfig['name'];
        $replaces = $this->commandConfiguration->getReplaces();
        if (in_array($commandConfig['name'], $replaces, true)
            || in_array($nameSpacedName, $replaces, true)
        ) {
            return;
        }
        if (isset($this->commands[$finalCommandName])) {
            $finalCommandName = $nameSpacedName;
        }
        if (isset($this->commands[$finalCommandName])) {
            throw new CommandNameAlreadyInUseException('Command "' . $finalCommandName . '" registered by "' . $commandConfig['vendor'] . '" is already in use', 1506531326);
        }
        $commandConfig['aliases'] = $commandConfig['aliases'] ?? [];
        if ($finalCommandName !== $nameSpacedName) {
            // Add alias to be able to call this command always with name spaced command name
            $commandConfig['aliases'][] = $nameSpacedName;
        }
        $commandConfig['name'] = $finalCommandName;
        $this->commands[$finalCommandName] = $commandConfig;
        foreach ($commandConfig['aliases'] as $alias) {
            $this->commands[$alias] = $commandConfig;
        }
    }
}
