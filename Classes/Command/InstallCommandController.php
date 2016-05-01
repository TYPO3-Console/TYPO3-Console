<?php
namespace Helhum\Typo3Console\Command;

/*
 * This file is part of the typo3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Alpha version of a setup command controller
 * Use with care and at your own risk!
 */
class InstallCommandController extends CommandController
{
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
     * @var \Helhum\Typo3Console\Install\PackageStatesGenerator
     * @inject
     */
    protected $packageStatesGenerator;

    /**
     * TYPO3 Setup. Use as cli replacement for the web installation process.
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
    public function setupCommand($nonInteractive = false, $databaseUserName = '', $databaseUserPassword = '', $databaseHostName = '', $databasePort = '', $databaseSocket = '', $databaseName = '', $databaseCreate = '', $adminUserName = '', $adminPassword = '', $siteName = 'New TYPO3 Console site')
    {
        $this->outputLine();
        $this->outputLine('<options=bold>Welcome to the console installer of TYPO3 CMS!</options=bold>');

        $this->cliSetupRequestHandler->setup(!$nonInteractive, $this->request->getArguments());

        $this->outputLine();
        $this->outputLine('Successfully installed TYPO3 CMS!');
    }

    /**
     * Writes the typo3conf/PackageStates.php file.
     *
     * Marks the following extensions as active in the process:
     * * third party extensions
     * * all core extensions that are required (or part of minimal usable system)
     * * all core extensions which are provided in the TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS environment variable
     * Extension keys in this variable must be separated by comma and without spaces.
     *
     * Example: TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS="info,info_pagetsconfig" ./typo3cms install:generatepackagestates
     *
     * @param bool $removeInactiveSystemExtensions Inactive extensions are removed from typo3/sysext (Handle with care!)
     * @param bool $activateDefaultExtensions  If true, typo3/cms extensions that are marked as TYPO3 factory default, will be activated, even if not in the list of configured active framework extensions.
     * @throws \TYPO3\CMS\Core\Package\Exception\InvalidPackageStateException
     */
    public function generatePackageStatesCommand($removeInactiveSystemExtensions = false, $activateDefaultExtensions = false)
    {
        $this->packageStatesGenerator->generate($this->packageManager, $activateDefaultExtensions);

        if ($removeInactiveSystemExtensions) {
            $activePackages = $this->packageManager->getActivePackages();
            foreach ($this->packageManager->getAvailablePackages() as $package) {
                if (empty($activePackages[$package->getPackageKey()])) {
                    $this->packageManager->unregisterPackage($package);
                    GeneralUtility::flushDirectory($package->getPackagePath());
                    $this->outputLine('Removed Package: ' . $package->getPackageKey());
                }
            }
            $this->packageManager->forceSortAndSavePackageStates();
        }
    }

    /**
     * Automatically create files and folders, required for a TYPO3 installation.
     *
     * This command is great e.g. for creating the typo3temp folder structure during deployment
     *
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception
     * @throws \TYPO3\CMS\Install\Status\Exception
     */
    public function fixFolderStructureCommand()
    {
        /** @var $folderStructureFactory \TYPO3\CMS\Install\FolderStructure\DefaultFactory */
        $folderStructureFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Install\FolderStructure\DefaultFactory::class);
        /** @var $structureFacade \TYPO3\CMS\Install\FolderStructure\StructureFacade */
        $structureFacade = $folderStructureFactory->getStructure();

        $fixedStatusObjects = $structureFacade->fix();

        if (empty($fixedStatusObjects)) {
            $this->outputLine('<info>No action performed!</info>');
        } else {
            $this->outputLine('<info>The following directory structure has been fixed:</info>');
            foreach ($fixedStatusObjects as $fixedStatusObject) {
                $this->outputLine($fixedStatusObject->getTitle());
            }
        }
    }

    /**
     * Check environment and create folder structure
     *
     * @internal
     */
    public function environmentAndFoldersCommand()
    {
        $this->cliSetupRequestHandler->executeActionWithArguments('environmentAndFolders');
    }

    /**
     * @internal
     */
    public function environmentAndFoldersNeedsExecutionCommand()
    {
        $this->cliSetupRequestHandler->callNeedsExecution('environmentAndFolders');
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
    public function databaseConnectCommand($databaseUserName = '', $databaseUserPassword = '', $databaseHostName = 'localhost', $databasePort = '3306', $databaseSocket = '')
    {
        $this->cliSetupRequestHandler->executeActionWithArguments('databaseConnect', array('host' => $databaseHostName, 'port' => $databasePort, 'username' => $databaseUserName, 'password' => $databaseUserPassword, 'socket' => $databaseSocket));
    }

    /**
     * @internal
     */
    public function databaseConnectNeedsExecutionCommand()
    {
        $this->cliSetupRequestHandler->callNeedsExecution('databaseConnect');
    }

    /**
     * Select a database name
     *
     * @param bool $databaseCreate Create database (1) or use existing database (0)
     * @param string $databaseName Name of the database
     * @internal
     */
    public function databaseSelectCommand($databaseCreate = true, $databaseName = 'required')
    {
        $selectType = $databaseCreate ? 'new' : 'existing';
        $this->cliSetupRequestHandler->executeActionWithArguments('databaseSelect', array('type' => $selectType, $selectType => $databaseName));
    }

    /**
     * @internal
     */
    public function databaseSelectNeedsExecutionCommand()
    {
        $this->cliSetupRequestHandler->callNeedsExecution('databaseSelect');
    }

    /**
     * Admin user and site name
     *
     * @param string $adminUserName Username of your first admin user
     * @param string $adminPassword Password of first admin user
     * @param string $siteName Site name
     * @internal
     */
    public function databaseDataCommand($adminUserName, $adminPassword, $siteName = 'New TYPO3 Console site')
    {
        $this->cliSetupRequestHandler->executeActionWithArguments('databaseData', array('username' => $adminUserName, 'password' => $adminPassword, 'sitename' => $siteName));
    }

    /**
     * @internal
     */
    public function databaseDataNeedsExecutionCommand()
    {
        $this->cliSetupRequestHandler->callNeedsExecution('databaseData');
    }

    /**
     * Write default configuration
     *
     * @param string $siteSetupType Specify the setup type: Download the list of distributions (loaddistribution), Create empty root page (createsite), Do nothing (donothing)
     * @internal
     */
    public function defaultConfigurationCommand($siteSetupType = 'createsite')
    {
        $this->cliSetupRequestHandler->executeActionWithArguments('defaultConfiguration', array('sitesetup' => $siteSetupType));
    }

    /**
     * @internal
     */
    public function defaultConfigurationNeedsExecutionCommand()
    {
        $this->cliSetupRequestHandler->callNeedsExecution('defaultConfiguration');
    }
}
