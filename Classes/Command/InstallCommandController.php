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
     * TYPO3 Setup
     *
     * Use as command line replacement for the web installation process.
     * Manually enter details on the command line or non interactive for automated setups.
     *
     * @param bool $nonInteractive If specified, optional arguments are not requested, but default values are assumed.
     * @param bool $force Force installation of TYPO3, even if <code>LocalConfiguration.php</code> file already exists.
     * @param string $databaseUserName User name for database server
     * @param string $databaseUserPassword User password for database server
     * @param string $databaseHostName Host name of database server
     * @param string $databasePort TCP Port of database server
     * @param string $databaseSocket Unix Socket to connect to (if localhost is given as hostname and this is kept empty, a socket connection will be established)
     * @param string $databaseName Name of the database
     * @param bool $useExistingDatabase If set an empty database with the specified name will be used. Otherwise a database with the specified name is created.
     * @param string $adminUserName User name of the administrative backend user account to be created
     * @param string $adminPassword Password of the administrative backend user account to be created
     * @param string $siteName Site Name
     * @param string $siteSetupType Can be either <code>no</code> (which unsurprisingly does nothing at all), <code>site</code> (which creates an empty root page and setup) or <code>dist</code> (which loads a list of distributions you can install)
     */
    public function setupCommand(
        $nonInteractive = false,
        $force = false,
        $databaseUserName = '',
        $databaseUserPassword = '',
        $databaseHostName = '',
        $databasePort = '',
        $databaseSocket = '',
        $databaseName = '',
        $useExistingDatabase = false,
        $adminUserName = '',
        $adminPassword = '',
        $siteName = 'New TYPO3 Console site',
        $siteSetupType = 'none'
    ) {
        $this->outputLine();
        $this->outputLine('<i>Welcome to the TYPO3 console installer!</i>');

        $this->ensureInstallationPossible($nonInteractive, $force);

        $this->cliSetupRequestHandler->setup(!$nonInteractive, $this->request->getArguments());

        $this->outputLine();
        $this->outputLine('Successfully installed TYPO3 CMS!');
    }

    /**
     * Generate PackageStates.php file
     *
     * Generates and writes <code>typo3conf/PackageStates.php</code> file.
     * Goal is to not have this file in version control, but generate it on <code>composer install</code>.
     *
     * Marks the following extensions as active:
     *
     * - Third party extensions
     * - All core extensions that are required (or part of minimal usable system)
     * - All core extensions which are provided in the TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS environment variable. Extension keys in this variable must be separated by comma and without spaces.
     *
     * <b>Example:</b> <code>TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS="info,info_pagetsconfig" ./typo3cms install:generatepackagestates</code>
     *
     * @param bool $removeInactive Inactive extensions are <comment>removed</comment> from <code>typo3/sysext</code>. <comment>Handle with care!</comment>
     * @param bool $activateDefault If true, <code>typo3/cms</code> extensions that are marked as TYPO3 factory default, will be activated, even if not in the list of configured active framework extensions.
     * @throws \TYPO3\CMS\Core\Package\Exception\InvalidPackageStateException
     * @throws \TYPO3\CMS\Core\Package\Exception\ProtectedPackageKeyException
     */
    public function generatePackageStatesCommand($removeInactive = false, $activateDefault = false)
    {
        $this->packageStatesGenerator->generate($this->packageManager, $activateDefault);

        if ($removeInactive) {
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
     * Fix folder structure
     *
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
     * Check environment / create folders
     *
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
     * Connect to database
     *
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
        $this->cliSetupRequestHandler->executeActionWithArguments('databaseConnect', ['host' => $databaseHostName, 'port' => $databasePort, 'username' => $databaseUserName, 'password' => $databaseUserPassword, 'socket' => $databaseSocket]);
    }

    /**
     * @internal
     */
    public function databaseConnectNeedsExecutionCommand()
    {
        $this->cliSetupRequestHandler->callNeedsExecution('databaseConnect');
    }

    /**
     * Select database
     *
     * Select a database by name
     *
     * @param bool $useExistingDatabase Use already existing database?
     * @param string $databaseName Name of the database
     * @internal
     */
    public function databaseSelectCommand($useExistingDatabase = false, $databaseName = 'required')
    {
        $selectType = $useExistingDatabase ? 'existing' : 'new';
        $this->cliSetupRequestHandler->executeActionWithArguments('databaseSelect', ['type' => $selectType, $selectType => $databaseName]);
    }

    /**
     * @internal
     */
    public function databaseSelectNeedsExecutionCommand()
    {
        $this->cliSetupRequestHandler->callNeedsExecution('databaseSelect');
    }

    /**
     * Add database data
     *
     * Adds admin user and site name in database
     *
     * @param string $adminUserName Username of to be created administrative user account
     * @param string $adminPassword Password of to be created administrative user account
     * @param string $siteName Site name
     * @internal
     */
    public function databaseDataCommand($adminUserName, $adminPassword, $siteName = 'New TYPO3 Console site')
    {
        $this->cliSetupRequestHandler->executeActionWithArguments('databaseData', ['username' => $adminUserName, 'password' => $adminPassword, 'sitename' => $siteName]);
    }

    /**
     * Check if database data command is needed
     *
     * @internal
     */
    public function databaseDataNeedsExecutionCommand()
    {
        $this->cliSetupRequestHandler->callNeedsExecution('databaseData');
    }

    /**
     * Write default configuration
     *
     * Writes default configuration for the TYPO3 site based on the
     * provided $siteSetupType. Valid values are:
     *
     * - dist (which loads a list of distributions you can install)
     * - site (which creates an empty root page and setup)
     * - no (which unsurprisingly does nothing at all)
     *
     * @param string $siteSetupType Specify the setup type: Download the list of distributions (dist), Create empty root page (site), Do nothing (no)
     * @internal
     */
    public function defaultConfigurationCommand($siteSetupType = 'no')
    {
        switch ($siteSetupType) {
            case 'site':
            case 'createsite':
                $argument = ['sitesetup' => 'createsite'];
                break;
            case 'dist':
            case 'loaddistribution':
                $argument = ['sitesetup' => 'loaddistribution'];
                break;
            case 'no':
            default:
                $argument = ['sitesetup' => 'none'];
        }
        $this->cliSetupRequestHandler->executeActionWithArguments('defaultConfiguration', $argument);
    }

    /**
     * Check if default configuration needs to be written
     *
     * @internal
     */
    public function defaultConfigurationNeedsExecutionCommand()
    {
        $this->cliSetupRequestHandler->callNeedsExecution('defaultConfiguration');
    }

    /**
     * Handles the case when LocalConfiguration.php file already exists
     *
     * @param $nonInteractive
     * @param $force
     */
    private function ensureInstallationPossible($nonInteractive, $force)
    {
        $localConfFile = PATH_typo3conf . 'LocalConfiguration.php';
        $packageStatesFile = PATH_typo3conf . 'PackageStates.php';
        if (!$force && file_exists($localConfFile)) {
            $this->outputLine();
            $this->outputLine('<error>TYPO3 seems to be already set up!</error>');
            $proceed = !$nonInteractive;
            if (!$nonInteractive) {
                $this->outputLine();
                $this->outputLine('<info>If you continue, your <code>typo3conf/LocalConfiguration.php</code></info>');
                $this->outputLine('<info>and <code>typo3conf/PackageStates.php</code> files will be deleted!</info>');
                $this->outputLine();
                $proceed = $this->output->askConfirmation('<info>Do you really want to proceed?</info> (<comment>no</comment>) ',
                    false);
            }
            if (!$proceed) {
                $this->outputLine('<error>Installation aborted!</error>');
                $this->quit(2);
            }
        }
        @unlink($localConfFile);
        @unlink($packageStatesFile);
        clearstatcache();
        if (file_exists($localConfFile)) {
            $this->outputLine();
            $this->outputLine('<error>Unable to delete configuration file!</error>');
            $this->outputLine('<error>Installation aborted!</error>');
            $this->quit(3);
        }
    }
}
