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
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Alpha version of a setup command controller
 * Use with care and at your own risk!
 */
class InstallCommandController extends CommandController {

	/**
	 * @var \TYPO3\CMS\Core\Package\PackageManager
	 * @inject
	 */
	protected $packageManager;

	/**
	 * @var \Helhum\Typo3Console\Install\CliSetupRequestHandler
	 * @inject
	 */
	protected $cliSetupRequestHandler;

	/**
	 * Alpha version of a setup command. Use with care and at your own risk!
	 *
	 * @param bool $nonInteractive
	 * @param string $databaseUserName
	 * @param string $databaseUserPassword
	 * @param string $databaseHostName
	 * @param string $databasePort
	 * @param string $databaseSocket
	 * @param string $databaseName
	 * @param string $databaseCreate
	 * @param string $adminUserName
	 * @param string $adminPassword
	 * @param string $siteName
	 */
	public function setupCommand($nonInteractive = FALSE, $databaseUserName = '', $databaseUserPassword = '', $databaseHostName = '', $databasePort = '', $databaseSocket = '', $databaseName = '', $databaseCreate = '', $adminUserName = '', $adminPassword = '', $siteName = 'New TYPO3 Console site') {
		$this->outputLine();
		$this->outputLine('<options=bold>Welcome to the console installer of TYPO3 CMS!</options=bold>');

		$this->cliSetupRequestHandler->setup(!$nonInteractive, $this->request->getArguments());

		$this->outputLine();
		$this->outputLine('Successfully installed TYPO3 CMS!');
	}

	/**
	 * Activates all packages that are configured in root composer.json or are required
	 *
	 * @param bool $removeInactivePackages
	 */
	public function generatePackageStatesCommand($removeInactivePackages = FALSE) {
		$installationPackages = $this->getPackagesFromRootComposerFile();
		foreach ($this->packageManager->getAvailablePackages() as $package) {
			if (
				in_array($package->getPackageKey(), $installationPackages)
				|| $package->isProtected()
				|| ($package instanceof Package && $package->isPartOfMinimalUsableSystem())
			) {
				$this->packageManager->activatePackage($package->getPackageKey());
			} else {
				$this->packageManager->deactivatePackage($package->getPackageKey());
				if ($removeInactivePackages) {
					$this->packageManager->unregisterPackage($package);
					GeneralUtility::flushDirectory($package->getPackagePath());
					$this->outputLine('Removed Package: ' . $package->getPackageKey());
				}
			}
		}

		$this->packageManager->forceSortAndSavePackageStates();
	}

	/**
	 * @return array Array of packages keys in root composer.json
	 */
	protected function getPackagesFromRootComposerFile() {
		if (!file_exists(PATH_site . 'composer.json')) {
			$this->outputLine('No composer.json found in project root');
			$this->sendAndExit(1);
		}

		$composerData = json_decode(file_get_contents(PATH_site . 'composer.json'));

		if (!is_object($composerData)) {
			$this->outputLine('composer.json seems to be invalid');
			$this->sendAndExit(1);
		}

		$activePackageKey = 'active-packages';
		if (!isset($composerData->extra->{$activePackageKey}) || !is_array($composerData->extra->{$activePackageKey})) {
			$this->outputLine('No packages found to activate!');
			$this->sendAndExit();
		}

		return $composerData->extra->{$activePackageKey};
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
	 * @param string $databaseName Name of the database
	 * @param bool $databaseCreate Create database (1) or use existing database (0)
	 * @internal
	 */
	public function databaseSelectCommand($databaseName, $databaseCreate = TRUE) {
		$selectType = $databaseCreate ? 'new' : 'existing';
		$this->cliSetupRequestHandler->executeActionWithArguments('databaseSelect', array('type' => $selectType, $selectType => $databaseName));
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