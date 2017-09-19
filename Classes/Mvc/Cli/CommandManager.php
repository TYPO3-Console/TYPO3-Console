<?php
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\Command;

/**
 * Class CommandManager
 */
class CommandManager extends \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager
{
    /**
     * @var array
     */
    protected $commandControllers = [];

    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * @var array
     */
    private $commandRegistry = [];

    /**
     * This lifecycle method is called by the object manager after instantiation
     * We can be sure dependencies have been injected.
     */
    public function initializeObject()
    {
        $this->initialized = true;
    }

    /**
     * Set the dependency
     */
    protected function initialize()
    {
        if (!$this->initialized) {
            $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        }
    }

    /**
     * @param string $commandIdentifier
     * @return \TYPO3\CMS\Extbase\Mvc\Cli\Command
     */
    public function getCommandByIdentifier($commandIdentifier)
    {
        $commandIdentifier = strtolower(trim($commandIdentifier));
        if ($commandIdentifier === 'help') {
            $commandIdentifier = 'typo3_console:help:help';
        }
        if ($commandIdentifier === 'autocomplete') {
            $commandIdentifier = 'typo3_console:help:autocomplete';
        }
        return parent::getCommandByIdentifier($commandIdentifier);
    }

    /**
     * @param Command $command
     * @return string
     */
    public function getShortestIdentifierForCommand(Command $command)
    {
        if ($command->getCommandIdentifier() === 'typo3_console:help:help') {
            return 'help';
        }
        if ($command->getCommandIdentifier() === 'typo3_console:help:autocomplete') {
            return 'autocomplete';
        }
        return parent::getShortestIdentifierForCommand($command);
    }

    /**
     * Make sure the object manager is set
     *
     * @param bool $onlyNew
     * @return Command[]
     */
    public function getAvailableCommands($onlyNew = false)
    {
        $this->initialize();
        if ($onlyNew) {
            $currentRegistry = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'];
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] = array_diff($currentRegistry, $this->commandRegistry);
            $availableCommands = $this->availableCommands;
            $this->availableCommands = null;
            $newCommands = parent::getAvailableCommands();
            $this->availableCommands = array_merge($availableCommands, $newCommands);
            $this->shortCommandIdentifiers = null;
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] = $currentRegistry;
            return $newCommands;
        }
        if ($this->availableCommands === null) {
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'])) {
                $this->commandControllers = array_merge(
                    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'],
                    $this->commandControllers
                );
            }
            $this->commandRegistry = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'];
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] = $this->commandControllers;
            $this->availableCommands = parent::getAvailableCommands();
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] = $this->commandRegistry;
        }
        return $this->availableCommands;
    }

    /**
     * @param string $commandControllerClassName
     */
    public function registerCommandController($commandControllerClassName)
    {
        if (!isset($this->commandControllers[$commandControllerClassName])) {
            $this->commandControllers[$commandControllerClassName] = $commandControllerClassName;
        } else {
            echo 'WARNING: command controller already registered!' . PHP_EOL;
        }
    }
}
