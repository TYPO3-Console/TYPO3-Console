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

/*                                                                        *
 * This script belongs to the Flow package "TYPO3.DocTools".              *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Core\ConsoleBootstrap;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\Command;
use TYPO3\CMS\Extbase\Mvc\Exception\CommandException;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * "Command Reference" command controller for the Documentation package.
 * Used to create reference documentation for TYPO3 Console CLI commands.
 */
class CommandReferenceCommandController extends CommandController
{
    /**
     * @var array
     */
    protected $settings = [
        'commandReferences' => [
            'typo3_console' => [
                'title' => 'Command Reference',
                'extensionKeys' => ['typo3_console', 'extensionmanager'],
            ]
        ]
    ];

    /**
     * @var Command[]
     */
    protected $commands = [];

    /**
     * @var \Helhum\Typo3Console\Mvc\Cli\CommandManager
     * @inject
     */
    protected $commandManager;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Renders command reference documentation from source code.
     *
     * @internal
     */
    public function renderCommand()
    {
        $this->renderReference('typo3_console');
    }

    /**
     * Render a CLI command reference to reStructuredText.
     *
     * @param string $reference
     * @return void
     */
    protected function renderReference($reference)
    {
        if (!isset($this->settings['commandReferences'][$reference])) {
            $this->outputLine('Command reference "%s" is not configured', array($reference));
            $this->quit(1);
        }
        $referenceConfiguration = $this->settings['commandReferences'][$reference];
        $extensionKeysToRender = $referenceConfiguration['extensionKeys'];
        array_walk($extensionKeysToRender, function (&$extensionKey) {$extensionKey = strtolower($extensionKey);});

        $availableCommands = $this->commandManager->getAvailableCommands();
        $commands = $this->buildCommandsIndex($availableCommands);
        $allCommands = array();
        foreach ($commands as $command) {

            if (!in_array(explode(':', $command->getCommandIdentifier())[0], $extensionKeysToRender, true)) {
                $this->outputLine(sprintf('<warning>Skipped command "%s"</warning>', $command->getCommandIdentifier()));
                continue;
            }
            $argumentDescriptions = array();
            $optionDescriptions = array();

            foreach ($command->getArgumentDefinitions() as $commandArgumentDefinition) {
                $argumentDescription = $commandArgumentDefinition->getDescription();
                if ($commandArgumentDefinition->isRequired()) {
                    $argumentDescriptions[$commandArgumentDefinition->getDashedName()] = $argumentDescription;
                } else {
                    $optionDescriptions[$commandArgumentDefinition->getDashedName()] = $argumentDescription;
                }
            }

            $relatedCommands = array();
            $relatedCommandIdentifiers = $command->getRelatedCommandIdentifiers();
            foreach ($relatedCommandIdentifiers as $relatedCommandIdentifier) {
                try {
                    $relatedCommand = $this->commandManager->getCommandByIdentifier($relatedCommandIdentifier);
                    $relatedCommands[$this->commandManager->getShortestIdentifierForCommand($relatedCommand)] = $relatedCommand->getShortDescription();
                } catch (CommandException $exception) {
                    $relatedCommands[$relatedCommandIdentifier] = '*Command not available*';
                }
            }

            $allCommands[$command->getCommandIdentifier()] = array(
                'identifier' => $this->commandManager->getShortestIdentifierForCommand($command),
                'shortDescription' => $this->transformMarkup($command->getShortDescription()),
                'description' => $this->transformMarkup($command->getDescription()),
                'options' => $this->transformMarkup($optionDescriptions),
                'arguments' => $this->transformMarkup($argumentDescriptions),
                'relatedCommands' => $relatedCommands
            );
        }

        $standaloneView = new StandaloneView();
        $templatePathAndFilename = 'EXT:typo3_console/Resources/Private/Templates/CommandReferenceTemplate.txt';
        $standaloneView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templatePathAndFilename));
        $standaloneView->assign('title', isset($referenceConfiguration['title']) ? $referenceConfiguration['title'] : $reference);
        $standaloneView->assign('allCommandsByPackageKey', ['typo3_console' => $allCommands]);
        $renderedOutputFile = GeneralUtility::getFileAbsFileName('EXT:typo3_console/Documentation/CommandReference/Index.rst');
        file_put_contents($renderedOutputFile, $standaloneView->render());
        $this->outputLine('DONE.');
    }

    /**
     * @param string $input
     * @return string
     */
    protected function transformMarkup($input)
    {
        $output =  preg_replace('|\<b>(((?!\</b>).)*)\</b>|', '**$1**', $input);
        $output =  preg_replace('|\<i>(((?!\</i>).)*)\</i>|', '*$1*', $output);
        $output =  preg_replace('|\<u>(((?!\</u>).)*)\</u>|', '*$1*', $output);
        $output =  preg_replace('|\<em>(((?!\</em>).)*)\</em>|', '*$1*', $output);
        $output =  preg_replace('|\<comment>(((?!\</comment>).)*)\</comment>|', '**$1**', $output);
        $output =  preg_replace('|\<warning>(((?!\</warning>).)*)\</warning>|', '**$1**', $output);
        $output =  preg_replace('|\<strike>(((?!\</strike>).)*)\</strike>|', '[$1]', $output);
        $output =  preg_replace('|\<code>(((?!\</code>).)*)\</code>|', '``$1``', $output);
        return $output;
    }

    /**
     * Builds an index of available commands. For each of them a Command object is
     * added to the commands array of this class.
     */
    protected function buildCommandsIndex()
    {
        $availableCommands = $this->commandManager->getAvailableCommands();
        /** @var RunLevel $runLevel */
        $runLevel = ConsoleBootstrap::getInstance()->getEarlyInstance(\Helhum\Typo3Console\Core\Booting\RunLevel::class);
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

        return $this->commands;
    }
}
