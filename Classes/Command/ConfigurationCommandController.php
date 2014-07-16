<?php
namespace Helhum\Typo3Console\Command;

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

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class ConfigurationCommandController
 */
class ConfigurationCommandController extends CommandController implements SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * Removing system configuration by path
	 *
	 * Example: ./typo3cms configuration:removebypath DB,EXT/EXTCONF/realurl
	 *
	 * @param array $paths Path to system configuration that should be removed. Multiple paths can be specified separated by comma
	 * @param bool $force If set, do not ask for confirmation
	 */
	public function removeByPathCommand(array $paths, $force = FALSE) {
		if (!$force) {
			do {
				$answer = strtolower($this->ask('Remove ' . implode(',', $paths) . ' from system configuration (TYPO3_CONF_VARS)? (y/N): '));
			} while ($answer !== 'y' && $answer !== 'yes');
			}
		$removed = $this->configurationManager->removeLocalConfigurationKeysByPath($paths);
		if (!$removed) {
			$this->outputLine('Paths seems invalid or empty. Nothing done!');
			$this->sendAndExit(1);
		}
		$this->outputLine('Removed from system configuration');
	}
} 