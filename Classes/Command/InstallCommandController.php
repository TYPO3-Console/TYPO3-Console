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

use Helhum\Typo3Console\Install\FolderStructure\ExtensionFactory;
use Helhum\Typo3Console\Install\PackageStatesGenerator;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\PackageInterface;

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
     * @var \Helhum\Typo3Console\Install\InstallStepActionExecutor
     * @inject
     */
    protected $installStepActionExecutor;

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
     * - All core extensions which are provided with the <code>--framework-extensions</code> argument.
     * - In composer mode all composer dependencies to TYPO3 framework extensions are detected and activated by default.
     *
     * To require TYPO3 core extensions use the following command:
     *
     * <code>composer require typo3/cms-foo "*"</code>
     *
     * This updates your composer.json and composer.lock without any other changes.
     *
     * <b>Example:</b> <code>typo3cms install:generatepackagestates</code>
     *
     * @param array $frameworkExtensions TYPO3 system extensions that should be marked as active. Extension keys separated by comma.
     * @param bool $activateDefault If true, <code>typo3/cms</code> extensions that are marked as TYPO3 factory default, will be activated, even if not in the list of configured active framework extensions.
     * @param array $excludedExtensions Extensions in typo3conf/ext/ directory, which should stay inactive
     */
    public function generatePackageStatesCommand(array $frameworkExtensions = [], $activateDefault = false, array $excludedExtensions = [])
    {
        $ranFromComposerPlugin = getenv('TYPO3_CONSOLE_PLUGIN_RUN') || !getenv('TYPO3_CONSOLE_FEATURE_GENERATE_PACKAGE_STATES');
        if (!$ranFromComposerPlugin && Bootstrap::usesComposerClassLoading()) {
            $this->output->outputLine('<warning>This command is now always automatically executed after Composer has written the autoload information.</warning>');
            $this->output->outputLine('<warning>It is therefore deprecated to be used in Composer mode.</warning>');
        }
        $frameworkExtensions = $frameworkExtensions ?: explode(',', (string)getenv('TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS'));
        $packageStatesGenerator = new PackageStatesGenerator($this->packageManager);
        $activatedExtensions = $packageStatesGenerator->generate($frameworkExtensions, $activateDefault, $excludedExtensions);

        $this->outputLine(
            '<info>The following extensions have been added to the generated PackageStates.php file:</info> %s',
            [
                implode(', ', array_map(function (PackageInterface $package) {
                    return $package->getPackageKey();
                }, $activatedExtensions)),
            ]
        );
        if (!empty($excludedExtensions)) {
            $this->outputLine(
                '<info>The following third party extensions were excluded during this process:</info> %s',
                [
                    implode(', ', $excludedExtensions),
                ]
            );
        }
    }

    /**
     * Fix folder structure
     *
     * Automatically create files and folders, required for a TYPO3 installation.
     *
     * This command creates the required folder structure needed for TYPO3 including extensions.
     * It is recommended to be executed <b>after</b> executing
     * <code>typo3cms install:generatepackagestates</code>, to ensure proper generation of
     * required folders for all active extensions.
     *
     * @see typo3_console:install:generatepackagestates
     *
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     * @throws \TYPO3\CMS\Install\FolderStructure\Exception\RootNodeException
     * @throws \TYPO3\CMS\Install\Status\Exception
     */
    public function fixFolderStructureCommand()
    {
        $folderStructureFactory = new ExtensionFactory($this->packageManager);
        $fixedStatusObjects = $folderStructureFactory
            ->getStructure()
            ->fix();

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
        $this->executeActionWithArguments('environmentAndFolders');
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
        $this->executeActionWithArguments('databaseConnect', ['host' => $databaseHostName, 'port' => $databasePort, 'username' => $databaseUserName, 'password' => $databaseUserPassword, 'socket' => $databaseSocket, 'driver' => 'mysqli']);
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
        $this->executeActionWithArguments('databaseSelect', ['type' => $selectType, $selectType => $databaseName]);
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
        $this->executeActionWithArguments('DatabaseData', ['username' => $adminUserName, 'password' => $adminPassword, 'sitename' => $siteName]);
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
                $arguments = ['sitesetup' => 'createsite'];
                break;
            case 'dist':
            case 'loaddistribution':
                $arguments = ['sitesetup' => 'loaddistribution'];
                break;
            case 'no':
            default:
                $arguments = ['sitesetup' => 'none'];
        }
        $this->executeActionWithArguments('defaultConfiguration', $arguments);
    }

    /**
     * Calls needs execution on the given action and returns the result
     *
     * @param string $actionName
     * @internal
     */
    public function actionNeedsExecutionCommand($actionName)
    {
        $this->executeActionWithArguments($actionName, [], true);
    }

    /**
     * Executes the given action and outputs the serialized result messages
     *
     * @param string $actionName Name of the install step
     * @param array $arguments Arguments for the install step
     * @param bool $dryRun If true, do not execute the action, but only check if execution is necessary
     */
    private function executeActionWithArguments($actionName, array $arguments = [], $dryRun = false)
    {
        $this->outputLine(serialize($this->installStepActionExecutor->executeActionWithArguments($actionName, $arguments, $dryRun)));
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

        if (!$force && @is_file($localConfFile)) {
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
        @unlink(is_link($localConfFile) ? readlink($localConfFile) : $localConfFile);
        @unlink($packageStatesFile);
        clearstatcache();
        if (@is_file($localConfFile)) {
            $this->outputLine();
            $this->outputLine('<error>Unable to delete configuration file!</error>');
            $this->outputLine('<error>Installation aborted!</error>');
            $this->quit(3);
        }
        //prewrite file to protect against the check in ConfigurationManager.php:273
        file_put_contents($localConfFile, "<?php return [];");
    }
}
