<?php
namespace Helhum\Typo3Console\Package;

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
use Helhum\Typo3Console\Mvc\Cli\RequestHandler;
use TYPO3\CMS\Core\Package\Exception;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Class UncachedPackageManager
 */
class UncachedPackageManager extends PackageManager {

	/**
	 * @var ConsoleBootstrap
	 */
	protected $bootstrap;

	/**
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 */
	public function initialize(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;

		$this->loadPackageStates();
		$this->initializePackageObjects();
		$this->initializeCompatibilityLoadedExtArray();

		foreach ($this->activePackages as $packageKey => $package) {
			/** @var $package Package */
			$package->boot($bootstrap);
		}

		if ($this->consolePackageBootRequired($this->getPackage('typo3_console'))) {
			// Force loading of the console in any case
			$this->activatePackage('typo3_console');
			$this->bootConsole();
		}
	}

	/**
	 * @param PackageInterface $consolePackage
	 * @return bool
	 */
	protected function consolePackageBootRequired($consolePackage) {
		return !$consolePackage instanceof \typo3_console\Package;
	}

	/**
	 * Boot up console (previously done in own package class)
	 */
	protected function bootConsole() {
		$this->bootstrap->registerRequestHandler(new RequestHandler($this->bootstrap));
		$this->registerCommands($this->bootstrap);
	}

	/**
	 * Register console commands, now only possible for typo3_console commands, not third party extensions :-/
	 */
	protected function registerCommands() {
		$this->bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\CacheCommandController');
		$this->bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\BackendCommandController');
		$this->bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\SchedulerCommandController');
		$this->bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\CleanupCommandController');
		$this->bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\DocumentationCommandController');
		$this->bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\InstallCommandController');
		$this->bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\ConfigurationCommandController');
		$this->bootstrap->getCommandManager()->registerCommandController('Helhum\Typo3Console\Command\FrontendCommandController');

		$this->bootstrap->setRunLevelForCommand('typo3_console:install:databasedata', RunLevel::LEVEL_MINIMAL);
		$this->bootstrap->addBootingStepForCommand('typo3_console:install:databasedata', 'helhum.typo3console:database');
		$this->bootstrap->setRunLevelForCommand('typo3_console:install:defaultconfiguration', RunLevel::LEVEL_FULL);
		$this->bootstrap->setRunLevelForCommand('typo3_console:install:*', RunLevel::LEVEL_COMPILE);

		$this->bootstrap->setRunLevelForCommand('typo3_console:cache:flush', RunLevel::LEVEL_COMPILE);
		$this->bootstrap->addBootingStepForCommand('typo3_console:cache:flush', 'helhum.typo3console:database');
		$this->bootstrap->setRunLevelForCommand('typo3_console:cache:*', RunLevel::LEVEL_FULL);

		$this->bootstrap->setRunLevelForCommand('typo3_console:backend:*', RunLevel::LEVEL_MINIMAL);
		$this->bootstrap->setRunLevelForCommand('typo3_console:documentation:*', RunLevel::LEVEL_FULL);
		$this->bootstrap->setRunLevelForCommand('typo3_console:scheduler:*', RunLevel::LEVEL_FULL);
		$this->bootstrap->setRunLevelForCommand('typo3_console:cleanup:*', RunLevel::LEVEL_FULL);
	}

	/**
	 * Intended to be called by the cache warmup only
	 * @internal
	 */
	public function populatePackageCache() {
		$this->saveToPackageCache();
	}

	protected function loadPackageStates() {
		$this->packageStatesConfiguration = file_exists($this->packageStatesPathAndFilename) ? include($this->packageStatesPathAndFilename) : array();
		if (!isset($this->packageStatesConfiguration['version']) || $this->packageStatesConfiguration['version'] < 4) {
			$this->packageStatesConfiguration = array();
		}
		if ($this->packageStatesConfiguration === array()) {
			$this->scanAvailablePackages();
		} else {
			$this->registerPackagesFromConfiguration();
		}
	}

	/**
	 * Only save a new PackageSates file if there is only one,
	 * to prevent saving one before TYPO3 is properly installed
	 */
	protected function sortAndSavePackageStates() {
		if (@file_exists($this->packageStatesPathAndFilename)) {
			parent::sortAndSavePackageStates();
		}
	}

	/**
	 * To enable writing of the package states file the package states
	 * migration needs to override eventual failsafe blocks.
	 * This will be used during installation process.
	 */
	public function forceSortAndSavePackageStates() {
		parent::sortAndSavePackageStates();
	}

}