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
use Helhum\Typo3Console\Mvc\Cli\Command;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Core\Bootstrap;

/**
 * A Command Controller which provides help for available commands
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class HelpCommandController extends CommandController
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager
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
     * @return void
     */
    public function helpCommand($commandIdentifier)
    {
        $command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
        $commandArgumentDefinitions = $command->getArgumentDefinitions();
        $this->outputLine('<comment>Usage:</comment>');
        $this->outputLine('  ' . $this->commandManager->getShortestIdentifierForCommand($command) . ' ' . $command->getSynopsis(true));
        $argumentDescriptions = [];
        $optionDescriptions = [];
        if ($command->hasArguments()) {
            foreach ($commandArgumentDefinitions as $commandArgumentDefinition) {
                $argumentDescription = $commandArgumentDefinition->getDescription();
                $argumentDescription = $this->wordWrap($argumentDescription, 23);
                if ($commandArgumentDefinition->isRequired()) {
                    $argumentDescriptions[] = vsprintf('  <info>%-20s</info> %s', [$commandArgumentDefinition->getName(), $argumentDescription]);
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
            foreach ($relatedCommandIdentifiers as $relatedIdentifier) {
                $command = $this->commandManager->getCommandByIdentifier($relatedIdentifier);
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
     * Builds an index of available commands. For each of them a Command object is
     * added to the commands array of this class.
     *
     * @return void
     */
    protected function buildCommandsIndex()
    {
        $availableCommands = $this->commandManager->getAvailableCommands();
        $runLevel = Bootstrap::getInstance()->getEarlyInstance(RunLevel::class);

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
