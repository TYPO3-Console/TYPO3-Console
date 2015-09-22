<?php
namespace Helhum\Typo3Console\Mvc\Cli;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CommandManager
 */
class CommandManager extends \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager {

	/**
	 * @var array
	 */
	protected $commandControllers = array();

	/**
	 * @var bool
	 */
	protected $initialized = FALSE;

	/**
	 * This lifecycle method is called by the object manager after instantiation
	 * We can be sure dependencies have been injected.
	 */
	public function initializeObject() {
		$this->initialized = TRUE;
	}

	/**
	 * Set the dependency
	 */
	protected function initialize() {
		if (!$this->initialized) {
			$this->objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
		}
	}

	/**
	 * Make sure the object manager is set
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Cli\Command[]
	 */
	public function getAvailableCommands() {
		$this->initialize();
		if ($this->availableCommands === NULL) {
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
	public function registerCommandController($commandControllerClassName) {
		if (!isset($this->commandControllers[$commandControllerClassName])) {
			$this->commandControllers[$commandControllerClassName] = $commandControllerClassName;
		} else {
			echo 'WARNING: command controller already registered!' . PHP_EOL;
		}
	}


}