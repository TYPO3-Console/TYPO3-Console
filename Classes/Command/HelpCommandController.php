<?php
namespace Helhum\Typo3Console\Command;

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
use Helhum\Typo3Console\Core\ConsoleBootstrap;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use TYPO3\CMS\Extbase\Mvc\Cli\Command;

/**
 * A Command Controller which provides help for available commands
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class HelpCommandController extends CommandController
{
    /**
     * Current version number
     *
     * @var string
     */
    private $version = '4.9.2';

    /**
     * @var \Helhum\Typo3Console\Mvc\Cli\CommandManager
     * @inject
     */
    protected $commandManager;

    /**
     * @var Command[]
     */
    protected $commands = [];

    /**
     * Help
     *
     * Display help for a command
     *
     * The help command displays help for a given command:
     * typo3cms help <command identifier>
     *
     * @param string $commandIdentifier Identifier of a command for more details
     * @param bool $raw Raw output of commands only
     * @return void
     */
    public function helpCommand($commandIdentifier = null, $raw = false)
    {
        if (!$raw) {
            $this->outputLine('<info>TYPO3 Console</info> version <comment>%s</comment>', [$this->version]);
            $this->outputLine();
        }

        if ($commandIdentifier === null) {
            $this->displayHelpIndex($raw);
        } else {
            try {
                $command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
            } catch (\TYPO3\CMS\Extbase\Mvc\Exception\CommandException $exception) {
                $this->outputLine($exception->getMessage());
                return;
            }
            $this->displayHelpForCommand($command);
        }
    }

    /**
     * @param bool $raw
     */
    protected function displayHelpIndex($raw = false)
    {
        $this->buildCommandsIndex();
        if (!$raw) {
            $this->outputLine('<comment>Usage:</comment>');
            $this->outputLine('  command [options] [arguments]');
            $this->outputLine();
            $this->outputLine('<comment>Available commands:</comment>');
        }

        foreach ($this->commands as $shortCommandIdentifier => $command) {
            $description = $command->getShortDescription();
            if (!$raw) {
                $description = $this->wordWrap($description, 43);
                $this->outputLine('%-2s<info>%-40s</info> %s', [' ', $shortCommandIdentifier, $description]);
            } else {
                $this->outputLine('%s %s', [$shortCommandIdentifier, $description]);
            }
        }

        if (!$raw) {
            $this->outputLine();
            $this->outputLine('See <info>help</info> <command> for more information about a specific command.');
            $this->outputLine();
        }
    }

    /**
     * Display help text for a single command
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Cli\Command $command
     * @return void
     */
    protected function displayHelpForCommand(\TYPO3\CMS\Extbase\Mvc\Cli\Command $command)
    {
        $this->outputLine($command->getShortDescription());
        $commandArgumentDefinitions = $command->getArgumentDefinitions();
        $usage = '';
        $hasOptions = false;
        foreach ($commandArgumentDefinitions as $commandArgumentDefinition) {
            if (!$commandArgumentDefinition->isRequired()) {
                $hasOptions = true;
            } else {
                $usage .= sprintf(' <%s>', strtolower(preg_replace('/([A-Z])/', ' $1', $commandArgumentDefinition->getName())));
            }
        }
        $usage = $this->commandManager->getShortestIdentifierForCommand($command) . ($hasOptions ? ' [<options>]' : '') . $usage;
        $this->outputLine();
        $this->outputLine('<comment>Usage:</comment>');
        $this->outputLine('  ' . $usage);
        $argumentDescriptions = [];
        $optionDescriptions = [];
        if ($command->hasArguments()) {
            foreach ($commandArgumentDefinitions as $commandArgumentDefinition) {
                $argumentDescription = $commandArgumentDefinition->getDescription();
                $argumentDescription = $this->wordWrap($argumentDescription, 23);
                if ($commandArgumentDefinition->isRequired()) {
                    $argumentDescriptions[] = vsprintf('  <info>%-20s</info> %s', [$commandArgumentDefinition->getDashedName(), $argumentDescription]);
                } else {
                    $optionDescriptions[] = vsprintf('  <info>%-20s</info> %s', [$commandArgumentDefinition->getDashedName(), $argumentDescription]);
                }
            }
        }
        if (count($argumentDescriptions) > 0) {
            $this->outputLine();
            $this->outputLine('<comment>Arguments:</comment>');
            foreach ($argumentDescriptions as $argumentDescription) {
                $this->outputLine($argumentDescription);
            }
        }
        if (count($optionDescriptions) > 0) {
            $this->outputLine();
            $this->outputLine('<comment>Options:</comment>');
            foreach ($optionDescriptions as $optionDescription) {
                $this->outputLine($optionDescription);
            }
        }
        if ($command->getDescription() !== '') {
            $this->outputLine();
            $this->outputLine('<comment>Help:</comment>');
            $descriptionLines = explode(chr(10), $command->getDescription());
            foreach ($descriptionLines as $descriptionLine) {
                $this->outputLine('%-2s%s', [' ', $descriptionLine]);
            }
        }
        $relatedCommandIdentifiers = $command->getRelatedCommandIdentifiers();
        if ($relatedCommandIdentifiers !== []) {
            $this->outputLine();
            $this->outputLine('<comment>Related Commands:</comment>');
            foreach ($relatedCommandIdentifiers as $commandIdentifier) {
                $command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
                $this->outputLine('%-2s%s (%s)', [' ', $this->commandManager->getShortestIdentifierForCommand($command), $command->getShortDescription()]);
            }
        }
        $this->outputLine();
    }

    /**
     * Displays an error message
     *
     * @internal
     * @param \TYPO3\CMS\Extbase\Mvc\Exception\CommandException $exception
     * @return void
     */
    public function errorCommand(\TYPO3\CMS\Extbase\Mvc\Exception\CommandException $exception)
    {
        $this->outputLine('<error>%s</error>', [$exception->getMessage()]);
        if ($exception instanceof \TYPO3\CMS\Extbase\Mvc\Exception\AmbiguousCommandIdentifierException) {
            $this->outputLine('Please specify the complete command identifier. Matched commands:');
            foreach ($exception->getMatchingCommands() as $matchingCommand) {
                $this->outputLine('    %s', [$matchingCommand->getCommandIdentifier()]);
            }
        }
        $this->outputLine();
        $this->outputLine('See <info>help</info> for an overview of all available commands');
        $this->outputLine('or <info>help</info> <command> for a detailed description of the corresponding command.');
        $this->quit(1);
    }

    /**
     * Generate shell auto complete script
     *
     * Inspired by and copied code from https://github.com/bamarni/symfony-console-autocomplete
     * See https://github.com/bamarni/symfony-console-autocomplete/blob/master/README.md
     * for a description how to install the script in your system.
     *
     * @param string $shell "bash" or "zsh"
     * @param array $aliases Aliases for the typo3cms command
     * @param bool $dynamic Dynamic auto completion is slower but more flexible
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function autoCompleteCommand($shell = 'bash', array $aliases = [], $dynamic = false)
    {
        if (!in_array($shell, ['zsh', 'bash'], true)) {
            $this->output->outputLine('<error>Shell can only be "bash" or "zsh"</error>');
            $this->quit(1);
        }
        $script = 'typo3cms';
        $tools = [$script];
        if ($aliases) {
            $aliases = array_filter(preg_split('/\s+/', implode(' ', $aliases)));
            $tools = array_unique(array_merge($tools, $aliases));
        }

        if ($dynamic) {
            // dump
            $template = file_get_contents(__DIR__ . '/../../Resources/Private/AutocompleteTemplates/default.' . $shell . '.tpl');
            if ('zsh' === $shell) {
                $tools = array_map(function ($v) {
                    return 'compdef _typo3console ' . $v;
                }, $tools);
            } else {
                $tools = array_map(function ($v) {
                    return 'complete -o default -F _typo3console ' . $v;
                }, $tools);
            }
            $this->output->output(str_replace(
                ['%%TOOLS%%'],
                [implode("\n", $tools)],
                $template
            ));
        } else {
            $this->buildCommandsIndex();
            $commandsDescriptions = [];
            $commandsOptionsDescriptions = [];
            $commandsOptions = [];
            $commands = [];
            foreach ($this->commands as $commandIdentifier => $command) {
                $commandIdentifier = strtolower($commandIdentifier);
                if ($commandIdentifier === 'help:autocomplete') {
                    $commandIdentifier = 'autocomplete';
                } elseif ($commandIdentifier === 'help:help') {
                    $commandIdentifier = 'help';
                }
                $commands[] = $commandIdentifier;
                $commandsDescriptions[$commandIdentifier] = $command->getShortDescription();
                $commandsOptionsDescriptions[$commandIdentifier] = [];
                if ($command->hasArguments()) {
                    $commandOptions = [];
                    foreach ($command->getArgumentDefinitions() as $commandArgumentDefinition) {
                        if (!$commandArgumentDefinition->isRequired()) {
                            $name = $commandArgumentDefinition->getDashedName();
                            $commandOptions[] = $name;
                        }
                    }
                    $commandsOptions[$commandIdentifier] = $commandOptions;
                }
            }
            $switchCaseStatementTemplate = 'opts="${opts} %%COMMAND_OPTIONS%%"';
            if ('zsh' === $shell) {
                $switchCaseStatementTemplate = 'opts+=(%%COMMAND_OPTIONS%%)';
            }
            // generate the switch content
            $switchCaseTemplate = <<<SWITCHCASE
        %%COMMAND%%)
                $switchCaseStatementTemplate
                ;;
SWITCHCASE;

            $switchContent = '';
            $zsh_describe = function ($value, $description = null) {
                $value = '"' . str_replace(':', '\\:', $value);
                if (!empty($description)) {
                    $value .= ':' . escapeshellcmd($description);
                }

                return $value . '"';
            };
            foreach ($commandsOptions as $command => $options) {
                if (empty($options)) {
                    continue;
                }
                if ('zsh' === $shell) {
                    $options = array_map(function ($option) use ($command, $commandsOptionsDescriptions, $zsh_describe) {
                        return $zsh_describe($option, $commandsOptionsDescriptions[$command][$option]);
                    }, $options);
                }

                $switchContent .= str_replace(
                    ['%%COMMAND%%', '%%COMMAND_OPTIONS%%'],
                    [$command, implode(' ', $options)],
                    $switchCaseTemplate
                ) . "\n        ";
            }
            $switchContent = rtrim($switchContent, ' ');

            // dump
            $template = file_get_contents(__DIR__ . '/../../Resources/Private/AutocompleteTemplates/cached.' . $shell . '.tpl');
            if ('zsh' === $shell) {
                $commands = array_map(function ($command) use ($commandsDescriptions, $zsh_describe) {
                    return $zsh_describe($command, $commandsDescriptions[$command]);
                }, $commands);

                $tools = array_map(function ($v) use ($script) {
                    return "compdef _$script $v";
                }, $tools);
            } else {
                $tools = array_map(function ($v) use ($script) {
                    return "complete -o default -F _$script $v";
                }, $tools);
            }

            $this->output->output(str_replace(
                ['%%SCRIPT%%', '%%COMMANDS%%', '%%SHARED_OPTIONS%%', '%%SWITCH_CONTENT%%', '%%TOOLS%%'],
                [$script, implode(' ', $commands), '', $switchContent, implode("\n", $tools)],
                $template
            ));
        }
    }

    /**
     * Builds an index of available commands. For each of them a Command object is
     * added to the commands array of this class.
     *
     * @return void
     */
    protected function buildCommandsIndex()
    {
        $availableCommands = $this->commandManager->getAvailableCommands();
        /** @var RunLevel $runLevel */
        $runLevel = ConsoleBootstrap::getInstance()->getEarlyInstance('Helhum\Typo3Console\Core\Booting\RunLevel');

        foreach ($availableCommands as $command) {
            if ($command->isInternal()) {
                continue;
            }

            $shortCommandIdentifier = $this->commandManager->getShortestIdentifierForCommand($command);

            if ($runLevel->getMaximumAvailableRunLevel() === RunLevel::LEVEL_COMPILE && !$runLevel->isCommandAvailable($shortCommandIdentifier)) {
                continue;
            }

            $this->commands[$shortCommandIdentifier] = $command;
        }

        ksort($this->commands);
    }

    /**
     * @param string $stringToWrap
     * @param int $indent
     * @return string
     */
    protected function wordWrap($stringToWrap, $indent)
    {
        $formatter = $this->output->getSymfonyConsoleOutput()->getFormatter();
        return wordwrap($formatter->format($stringToWrap), $this->output->getMaximumLineLength() - $indent, PHP_EOL . str_repeat(' ', $indent), true);
    }
}
