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
    public function setupCommand($nonInteractive = false, $databaseUserName = '', $databaseUserPassword = '', $databaseHostName = '', $databasePort = '', $databaseSocket = '', $databaseName = '', $databaseCreate = '', $adminUserName = '', $adminPassword = '', $siteName = 'New TYPO3 Console site')
    {
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
    public function generatePackageStatesCommand($removeInactivePackages = false)
    {
        try {
            $installationPackages = $this->getPackagesFromRootComposerFile();
        } catch (\Exception $e) {
            $this->outputLine('<error>' . $e->getMessage() . '</error>');
            $this->quit(1);
            return;
        }
        foreach ($this->packageManager->getAvailablePackages() as $package) {
            if (
                isset($installationPackages[$package->getPackageKey()])
                || $package->isProtected()
                || ($package instanceof Package && $package->isPartOfMinimalUsableSystem())
            ) {
                $this->packageManager->activatePackage($package->getPackageKey());
            } else {
                try {
                    $this->packageManager->deactivatePackage($package->getPackageKey());
                } catch (\UnexpectedValueException $exception) {
                    $this->outputLine(
                        '<info>Error while deactivating package %s. Exception: %s</info>',
                        array($package->getPackageKey(), $exception->getMessage())
                    );
                }
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
    protected function getPackagesFromRootComposerFile()
    {
        // Look up configured active packages
        $configuredPackages = array();
        if (file_exists(PATH_site . 'composer.json')) {
            $composerData = json_decode(file_get_contents(PATH_site . 'composer.json'));
            if (!is_object($composerData)) {
                throw new \RuntimeException('composer.json seems to be invalid', 1444596471);
            }
            if (isset($composerData->extra->{'helhum/typo3-console'}->{'active-packages'})) {
                $configuredPackages = $composerData->extra->{'helhum/typo3-console'}->{'active-packages'};
            } elseif (isset($composerData->extra->{'active-packages'})) {
                $configuredPackages = $composerData->extra->{'active-packages'};
                $this->outputLine('<warning>Active packages configuration key changed. Please use extra["helhum/typo3-console"]["active-packages"]</warning>');
            }
            if (!is_array($configuredPackages)) {
                throw new \RuntimeException('Active packages is not an array!', 1444656020);
            }
        }

        // Determine non typo3-cms-extension packages installed by composer
        $composerInstalledPackages = array();
        $composerLockFile = PATH_site . 'composer.lock';
        if (file_exists($composerLockFile)) {
            $composerLock = json_decode(file_get_contents($composerLockFile));
            if ($composerLock) {
                foreach ($composerLock->packages as $package) {
                    if (!in_array($package->type, array('typo3-cms-core', 'typo3-cms-framework'), true) && (!empty($package->source) || !empty($package->dist))) {
                        $composerInstalledPackages[] = $this->getPackageKeyFromManifest($package);
                    }
                }
            }
        }

        $allPackages = array_flip(array_merge($configuredPackages, $composerInstalledPackages));
        if (empty($allPackages)) {
            $this->outputLine('<warning>No packages found to activate! Only marking required and important TYPO3 packages as active!</warning>');
        }
        return $allPackages;
    }

    /**
     * Resolves package key from Composer manifest
     *
     * If it is a TYPO3 package the name of the replaces section will be used.
     *
     * Else if the composer name of the package matches the first part of the lowercased namespace of the package, the mixed
     * case version of the composer name / namespace will be used, with backslashes replaced by dots.
     *
     * Else the composer name will be used with the slash replaced by a dot
     *
     * @param object $manifest
     * @return string
     */
    protected function getPackageKeyFromManifest($manifest)
    {
        if (isset($manifest->type) && substr($manifest->type, 0, 10) === 'typo3-cms-') {
            if (!empty($manifest->replace)) {
                $replaces = array_flip(json_decode(json_encode($manifest->replace), true));
                foreach ($replaces as $replacedName) {
                    if (strpos($replacedName, '/') === false) {
                        $extensionKey = $replacedName;
                        break;
                    }
                }
            }
            if (empty($extensionKey)) {
                list(, $extensionKey) = explode('/', $manifest->name, 2);
                $extensionKey = str_replace('-', '_', $extensionKey);
            }
            return $extensionKey;
        } else {
            $packageKey = str_replace('/', '.', $manifest->name);
            return preg_replace('/[^A-Za-z0-9.]/', '', $packageKey);
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
     * Select a database name
     *
     * @param string $databaseName Name of the database
     * @param bool $databaseCreate Create database (1) or use existing database (0)
     * @internal
     */
    public function databaseSelectCommand($databaseName, $databaseCreate = true)
    {
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
    public function databaseDataCommand($adminUserName, $adminPassword, $siteName = 'New TYPO3 Console site')
    {
        $this->cliSetupRequestHandler->executeActionWithArguments('databaseData', array('username' => $adminUserName, 'password' => $adminPassword, 'sitename' => $siteName));
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
}
