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
use Helhum\Typo3Console\Mvc\Cli\Symfony\Command\ErroredCommand;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
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
     * @var CommandConfiguration
     */
    private $commandConfiguration;

    /**
     * @var array
     */
    private $commands = [];

    /**
     * @var BaseCommand[]
     */
    private $erroredCommands = [];

    /**
     * @var string[]
     */
    private $replaces = [];

    /**
     * @var Typo3CommandRegistry
     */
    private $typo3CommandRegistry;

    public function __construct(CommandConfiguration $commandConfiguration, Typo3CommandRegistry $typo3CommandRegistry)
    {
        $this->commandConfiguration = $commandConfiguration;
        $this->typo3CommandRegistry = $typo3CommandRegistry;
        $this->populateCommands();
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
        if (!isset($this->commands[$name])) {
            throw new CommandNotFoundException(sprintf('The command "%s" does not exist.', $name), [], 1518812618);
        }
        if (isset($this->erroredCommands[$this->commands[$name]['name']])) {
            return $this->erroredCommands[$this->commands[$name]['name']];
        }
        $commandConfig = $this->commands[$name];
        try {
            if ($commandConfig['service']) {
                return $this->typo3CommandRegistry->get($name);
            }
            if (isset($commandConfig['class'])) {
                /** @var BaseCommand $command */
                $command = GeneralUtility::makeInstance($commandConfig['class'], $commandConfig['name']);
            } else {
                throw new CommandNotFoundException(sprintf('The command "%s" does not exist.', $name), [], 1520205204);
            }

            if (!empty($this->commands[$name]['aliases'])) {
                $command->setAliases($this->commands[$name]['aliases']);
            }

            return $command;
        } catch (\Throwable $e) {
            if (isset($commandConfig['runLevel'])) {
                // Do not hide object creation errors in lowlevel commands
                throw $e;
            }

            return $this->erroredCommands[$commandConfig['name']] = new ErroredCommand($commandConfig['name'], $e);
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name): bool
    {
        return isset($this->commands[$name]);
    }

    /**
     * @return string[]
     */
    public function getNames(): array
    {
        return array_keys($this->commands);
    }

    private function populateCommands(): void
    {
        $definitions = array_merge($this->getTypo3CommandDefinitions(), $this->commandConfiguration->getCommandDefinitions());
        $this->replaces = $this->commandConfiguration->getReplaces();
        foreach ($definitions as $commandConfig) {
            $this->add($commandConfig);
        }
    }

    private function getTypo3CommandDefinitions(): array
    {
        $definitions = [];
        foreach ($this->typo3CommandRegistry->getCommandConfiguration() as $commandName => $commandConfig) {
            $definitions[] = [
                'name' => $commandName,
                'vendor' => $commandName,
                'nameSpacedName' => $commandName,
                'class' => $commandConfig['class'],
                'service' => true,
            ];
        }

        return $definitions;
    }

    /**
     * @param RunLevel $runLevel
     * @internal
     */
    public function initializeRunLevel(Runlevel $runLevel): void
    {
        foreach ($this->commands as $name => $commandConfig) {
            if (isset($commandConfig['runLevel'])) {
                $runLevel->setRunLevelForCommand($name, $commandConfig['runLevel']);
            }
            if (isset($commandConfig['bootingSteps'])) {
                foreach ($commandConfig['bootingSteps'] as $bootingStep) {
                    $runLevel->addBootingStepForCommand($name, $bootingStep);
                }
            }
        }
    }

    private function add(array $commandConfig): void
    {
        if (isset($this->commands[$commandConfig['name']])
            && $this->commands[$commandConfig['name']]['service']
            && $this->commands[$commandConfig['name']]['class'] === $commandConfig['class']
        ) {
            if (isset($commandConfig['runLevel'])) {
                throw new RuntimeException(sprintf('Command "%s" is registered as service. Setting runLevel via configuration is not supported in that case.', $commandConfig['name']), 1589019018);
            }
            // Command is also registered as service. Ignoring legacy registration
            return;
        }
        $finalCommandName = $commandConfig['name'];
        if (in_array($commandConfig['class'], $this->replaces, true)) {
            return;
        }
        if (isset($this->commands[$finalCommandName])) {
            $finalCommandName = $commandConfig['nameSpacedName'];
        }
        if (isset($this->commands[$finalCommandName])) {
            throw new CommandNameAlreadyInUseException('Command "' . $finalCommandName . '" registered by "' . $commandConfig['vendor'] . '" is already in use', 1506531326);
        }
        $commandConfig['aliases'] = $commandConfig['aliases'] ?? [];
        if ($finalCommandName !== $commandConfig['nameSpacedName']) {
            // Add alias to be able to call this command always with name spaced command name
            $commandConfig['aliases'][] = $commandConfig['nameSpacedName'];
        }
        $commandConfig['name'] = $finalCommandName;
        $this->commands[$finalCommandName] = $commandConfig;
        foreach ($commandConfig['aliases'] ?? [] as $alias) {
            $this->commands[$alias] = $commandConfig;
        }
    }
}
