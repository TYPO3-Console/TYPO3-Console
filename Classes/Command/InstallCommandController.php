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

use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Core\ConsoleBootstrap;
use Helhum\Typo3Console\Mvc\Controller\CommandController;

/**
 * Pre-alpha version of a setup command controller
 * Use with care and at your own risk!
 */
class InstallCommandController extends CommandController {

	/**
	 * @var \Helhum\Typo3Console\Install\Mcv\Dispatcher
	 * @inject
	 */
	protected $dispatcher;

	/**
	 * Pre-alpha version of a setup command. Use with care and at your own risk!
	 *
	 * @param string $databaseUserName
	 * @param string $databaseUserPassword
	 * @param string $databaseHostName
	 * @param string $databasePort
	 * @param string $databaseSocket
	 * @param string $databaseName
	 * @param string $adminUserName
	 * @param string $adminPassword
	 * @param string $siteName
	 */
	public function setupCommand($databaseUserName = '', $databaseUserPassword = '', $databaseHostName = '', $databasePort = '', $databaseSocket = '', $databaseName = '', $adminUserName = '', $adminPassword = '', $siteName = 'New TYPO3 Console site') {
		touch(PATH_site . 'FIRST_INSTALL');
		$this->outputLine();
		$this->outputLine('Thank you for choosing TYPO3 CMS!');
		$this->outputLine('Please give me some information about your system:');

		$this->forward('environmentAndFolders', NULL, $this->request->getArguments());
	}

	/**
	 * @param string $databaseUserName
	 * @param string $databaseUserPassword
	 * @param string $databaseHostName
	 * @param string $databasePort
	 * @param string $databaseSocket
	 * @param string $databaseName
	 * @param string $adminUserName
	 * @param string $adminPassword
	 * @param string $siteName
	 * @internal
	 */
	public function environmentAndFoldersCommand($databaseUserName = '', $databaseUserPassword = '', $databaseHostName = '', $databasePort = '', $databaseSocket = '', $databaseName = '', $adminUserName = '', $adminPassword = '', $siteName = 'New TYPO3 Console site') {
		$messages = $this->dispatcher->dispatchAction('environmentAndFolders');
		$this->outputLine();
		$this->outputLine('<options=bold>Database<options=bold> credentials:');

		$this->forward('databaseConnect', NULL, $this->request->getArguments());
	}

	/**
	 * @param string $databaseUserName
	 * @param string $databaseUserPassword
	 * @param string $databaseHostName
	 * @param string $databasePort
	 * @param string $databaseSocket
	 * @param string $databaseName
	 * @param string $adminUserName
	 * @param string $adminPassword
	 * @param string $siteName
	 * @internal
	 */
	public function databaseConnectCommand($databaseUserName, $databaseUserPassword, $databaseHostName, $databasePort, $databaseSocket = '', $databaseName = '', $adminUserName = '', $adminPassword = '', $siteName = 'New TYPO3 Console site') {
		$messages = $this->dispatcher->dispatchAction('databaseConnect', array('host' => $databaseHostName, 'port' => $databasePort, 'username' => $databaseUserName, 'password' => $databaseUserPassword, 'socket' => $databaseSocket));
		$this->outputLine();
		$this->outputLine('<options=bold>Database<options=bold> name:');

		$this->forward('databaseSelect', NULL, $this->request->getArguments());
	}

	/**
	 * @param string $databaseName
	 * @param string $adminUserName
	 * @param string $adminPassword
	 * @param string $siteName
	 * @internal
	 */
	public function databaseSelectCommand($databaseName, $adminUserName = '', $adminPassword = '', $siteName = 'New TYPO3 Console site') {
		$messages = $this->dispatcher->dispatchAction('databaseSelect', array('type' => 'new', 'new' => $databaseName));
		$this->outputLine();
		$this->outputLine('<options=bold>Backend<options=bold> user and password:');

		$this->forward('databaseData', NULL, $this->request->getArguments());
	}

	/**
	 * @param string $adminUserName
	 * @param string $adminPassword
	 * @param string $siteName
	 * @internal
	 */
	public function databaseDataCommand($adminUserName, $adminPassword, $siteName = 'New TYPO3 Console site') {
		// todo: get rid of this dependency!
		ConsoleBootstrap::getInstance()->requestRunLevel(RunLevel::LEVEL_FULL);
		$messages = $this->dispatcher->dispatchAction('databaseData', array('username' => $adminUserName, 'password' => $adminPassword, 'sitename' => $siteName));

		$this->executeLastStepAsExternalCommand();
		$this->outputLine('Successfully installed TYPO3 CMS!');
	}

	/**
	 * To avoid the hard coded exit in this step,
	 * we call ourselves "externally" to be able to add additional steps after the mandatory ones
	 *
	 * @return mixed
	 */
	protected function executeLastStepAsExternalCommand() {
		$phpBinary = defined('PHP_BINARY') ? PHP_BINARY : (!empty($_SERVER['_']) ? $_SERVER['_'] : '');
		$commandLine = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();
		$callingScript = array_shift($commandLine);
		$commandLine[0] = 'install:defaultconfiguration';
		$scriptToExecute = !empty($phpBinary) ? (escapeshellcmd($phpBinary) . ' ') : '' . escapeshellcmd($callingScript) . ' ' . implode(' ', array_map('escapeshellarg', $commandLine));

		exec($scriptToExecute, $output, $returnValue);
		return $returnValue;
	}

	/**
	 * This command will be called directly
	 *
	 * @internal
	 */
	public function defaultConfigurationCommand() {
		// After this action there is a hard coded exit!
		$this->dispatcher->dispatchAction('defaultConfiguration');
	}
}