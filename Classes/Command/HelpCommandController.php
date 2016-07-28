<?php
namespace Helhum\Typo3Console\Command;

/*
 * This file is part of the TYPO3 console project.
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
    private $version = '3.2.2';

    /**
     * @var \Helhum\Typo3Console\Mvc\Cli\CommandManager
     * @inject
     */
    protected $commandManager;

    /**
     * @var Command[]
     */
    protected $commands = array();

    /**
     * Help
     *
     * Display help for a command
     *
     * The help command displays help for a given command:
     * ./typo3cms help <command identifier>
     *
     * @param string $commandIdentifier Identifier of a command for more details
     * @return void
     */
    public function helpCommand($commandIdentifier = null)
    {
        $this->outputLine('<info>TYPO3 Console</info> version <comment>%s</comment>', array($this->version));
        $this->outputLine();

        if ($commandIdentifier === null) {
            $this->displayHelpIndex();
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
     * @return void
     */
    protected function displayHelpIndex()
    {
        $this->buildCommandsIndex();
        $this->outputLine('<comment>Usage:</comment>');
        $this->outputLine('  command [options] [arguments]');
        $this->outputLine();
        $this->outputLine('<comment>Available commands:</comment>');

        foreach ($this->commands as $shortCommandIdentifier => $command) {
            $description = $this->wordWrap($command->getShortDescription(), 43);
            $this->outputLine('%-2s<info>%-40s</info> %s', array(' ', $shortCommandIdentifier, $description));
        }

        $this->outputLine();
        $this->outputLine('See <info>help</info> <command> for more information about a specific command.');
        $this->outputLine();
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
        $argumentDescriptions = array();
        $optionDescriptions = array();
        if ($command->hasArguments()) {
            foreach ($commandArgumentDefinitions as $commandArgumentDefinition) {
                $argumentDescription = $commandArgumentDefinition->getDescription();
                $argumentDescription = $this->wordWrap($argumentDescription, 23);
                if ($commandArgumentDefinition->isRequired()) {
                    $argumentDescriptions[] = vsprintf('  <info>%-20s</info> %s', array($commandArgumentDefinition->getDashedName(), $argumentDescription));
                } else {
                    $optionDescriptions[] = vsprintf('  <info>%-20s</info> %s', array($commandArgumentDefinition->getDashedName(), $argumentDescription));
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
                $this->outputLine('%-2s%s', array(' ', $descriptionLine));
            }
        }
        $relatedCommandIdentifiers = $command->getRelatedCommandIdentifiers();
        if ($relatedCommandIdentifiers !== array()) {
            $this->outputLine();
            $this->outputLine('<comment>Related Commands:</comment>');
            foreach ($relatedCommandIdentifiers as $commandIdentifier) {
                $command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
                $this->outputLine('%-2s%s (%s)', array(' ', $this->commandManager->getShortestIdentifierForCommand($command), $command->getShortDescription()));
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
        $this->outputLine('<info>TYPO3 Console</info> version <comment>%s</comment>', array(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('typo3_console')));
        $this->outputLine();
        $this->outputLine('<error>%s</error>', array($exception->getMessage()));
        if ($exception instanceof \TYPO3\CMS\Extbase\Mvc\Exception\AmbiguousCommandIdentifierException) {
            $this->outputLine('Please specify the complete command identifier. Matched commands:');
            foreach ($exception->getMatchingCommands() as $matchingCommand) {
                $this->outputLine('    %s', array($matchingCommand->getCommandIdentifier()));
            }
        }
        $this->outputLine('');
        $this->outputLine('See <info>help</info> for an overview of all available commands');
        $this->outputLine('or <info>help</info> <command> for a detailed description of the corresponding command.');
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
