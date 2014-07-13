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

/**
 * Pre-alpha version of a setup command controller
 * Use with care and at your own risk!
 */
class InstallCommandController extends CommandController {

	/**
	 * @var \Helhum\Typo3Console\Install\CliSetupRequestHandler
	 * @inject
	 */
	protected $cliSetupRequestHandler;

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
		$this->outputLine();
		$this->outputLine('<options=bold>Welcome to the console installer of TYPO3 CMS!</options=bold>');

		$this->cliSetupRequestHandler->setup($this->request->getArguments());

		$this->outputLine();
		$this->outputLine('Successfully installed TYPO3 CMS!');
}

	/**
	 * Check environment and create folder structure
	 *
	 * @internal
	 */
	public function environmentAndFoldersCommand() {
		$this->cliSetupRequestHandler->executeActionWithArguments('environmentAndFolders');
	}

	/**
	 * Database connection details
	 *
	 * @param string $databaseUserName User name for database server
	 * @param string $databaseUserPassword User password for database server
	 * @param string $databaseHostName Host name of database server
	 * @param string $databasePort TCP Port of database server
	 * @param string $databaseSocket Unix Socket to connect to
	 * @internal
	 */
	public function databaseConnectCommand($databaseUserName = '', $databaseUserPassword = '', $databaseHostName = 'localhost', $databasePort = '3306', $databaseSocket = '') {
		$this->cliSetupRequestHandler->executeActionWithArguments('databaseConnect', array('host' => $databaseHostName, 'port' => $databasePort, 'username' => $databaseUserName, 'password' => $databaseUserPassword, 'socket' => $databaseSocket));
	}

	/**
	 * Select a database name
	 *
	 * @param string $databaseName Name of the database (will be created)
	 * @internal
	 */
	public function databaseSelectCommand($databaseName) {
		$this->cliSetupRequestHandler->executeActionWithArguments('databaseSelect', array('type' => 'new', 'new' => $databaseName));
	}

	/**
	 * Admin user and site name
	 *
	 * @param string $adminUserName Username of your first admin user
	 * @param string $adminPassword Password of first admin user
	 * @param string $siteName Site name
	 * @internal
	 */
	public function databaseDataCommand($adminUserName, $adminPassword, $siteName = 'New TYPO3 Console site') {
		$this->cliSetupRequestHandler->executeActionWithArguments('databaseData', array('username' => $adminUserName, 'password' => $adminPassword, 'sitename' => $siteName));
	}

	/**
	 * Write default configuration
	 *
	 * @internal
	 */
	public function defaultConfigurationCommand() {
		$this->cliSetupRequestHandler->executeActionWithArguments('defaultConfiguration');
	}
}