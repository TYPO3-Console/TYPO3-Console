<?php
namespace Helhum\Typo3Console\Mvc\Cli;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CommandManager
 */
class CommandManager extends \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager
{
    /**
     * @var array
     */
    protected $commandControllers = array();

    /**
     * @var bool
     */
    protected $initialized = false;

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
     * Make sure the object manager is set
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Cli\Command[]
     */
    public function getAvailableCommands()
    {
        $this->initialize();
        if ($this->availableCommands === null) {
            $commandControllerRegistry = & $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'];
            if (!empty($commandControllerRegistry) && is_array($commandControllerRegistry)) {
                $commandControllerRegistry = array_merge($commandControllerRegistry, $this->commandControllers);
            } else {
                $commandControllerRegistry = $this->commandControllers;
            }
            $this->availableCommands = parent::getAvailableCommands();
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
