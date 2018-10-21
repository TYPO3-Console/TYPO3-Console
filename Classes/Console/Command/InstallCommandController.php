<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command;

/*
 * This file is part of the TYPO3 Console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Helhum\Typo3Console\Annotation\Command\Definition;
use Helhum\Typo3Console\Core\Booting\CompatibilityScripts;
use Helhum\Typo3Console\Install\Action\InstallActionDispatcher;
use Helhum\Typo3Console\Install\FolderStructure\ExtensionFactory;
use Helhum\Typo3Console\Install\InstallStepActionExecutor;
use Helhum\Typo3Console\Install\PackageStatesGenerator;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Alpha version of a setup command controller
 * Use with care and at your own risk!
 */
class InstallCommandController extends CommandController
{
    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var InstallStepActionExecutor
     */
    protected $installStepActionExecutor;

    public function __construct(PackageManager $packageManager, InstallStepActionExecutor $installStepActionExecutor)
    {
        $this->packageManager = $packageManager;
        $this->installStepActionExecutor = $installStepActionExecutor;
    }

    /**
     * TYPO3 Setup
     *
     * Use as command line replacement for the web installation process.
     * Manually enter details on the command line or non interactive for automated setups.
     * As an alternative for providing command line arguments, it is also possible to provide environment variables.
     * Command line arguments take precedence over environment variables.
     * The following environment variables are evaluated:
     *
     * - TYPO3_INSTALL_DB_USER
     * - TYPO3_INSTALL_DB_PASSWORD
     * - TYPO3_INSTALL_DB_HOST
     * - TYPO3_INSTALL_DB_PORT
     * - TYPO3_INSTALL_DB_UNIX_SOCKET
     * - TYPO3_INSTALL_DB_USE_EXISTING
     * - TYPO3_INSTALL_DB_DBNAME
     * - TYPO3_INSTALL_ADMIN_USER
     * - TYPO3_INSTALL_ADMIN_PASSWORD
     * - TYPO3_INSTALL_SITE_NAME
     * - TYPO3_INSTALL_SITE_SETUP_TYPE
     * - TYPO3_INSTALL_WEB_SERVER_CONFIG
     *
     * @param bool $force Force installation of TYPO3, even if <code>LocalConfiguration.php</code> file already exists.
     * @param bool $skipIntegrityCheck Skip the checking for clean state before executing setup. This allows a pre-defined <code>LocalConfiguration.php</code> to be present. Handle with care. It might lead to unexpected or broken installation results.
     * @param bool $skipExtensionSetup Skip setting up extensions after TYPO3 is set up. Defaults to false in composer setups and to true in non composer setups.
     * @param string $installStepsConfig Override install steps with the ones given in this file
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
     * @param string $webServerConfig Web server config file to install in document root (<code>none</code>, <code>apache</code>, <code>iis</code>)
     * @param string $siteSetupType Can be either <code>no</code> (which unsurprisingly does nothing at all) or <code>site</code> (which creates an empty root page and setup)
     * @param bool $nonInteractive Deprecated. Use <code>--no-interaction</code> instead.
     */
    public function setupCommand(
        $force = false,
        $skipIntegrityCheck = false,
        $skipExtensionSetup = false,
        $installStepsConfig = null,
        $databaseUserName = '',
        $databaseUserPassword = '',
        $databaseHostName = '127.0.0.1',
        $databasePort = '3306',
        $databaseSocket = '',
        $databaseName = null,
        $useExistingDatabase = false,
        $adminUserName = null,
        $adminPassword = null,
        $siteName = 'New TYPO3 Console site',
        $webServerConfig = 'none',
        $siteSetupType = 'no',
        $nonInteractive = false
    ) {
        $isInteractive = $this->output->getSymfonyConsoleInput()->isInteractive();
        if ($nonInteractive) {
            // @deprecated in 5.0 will be removed with 6.0
            $this->outputLine('<warning>Option --non-interactive is deprecated. Please use --no-interaction instead.</warning>');
            $isInteractive = false;
        }

        $this->outputLine();
        $this->outputLine('<i>Welcome to the TYPO3 Console installer!</i>');
        $this->outputLine();

        $installActionDispatcher = new InstallActionDispatcher($this->output);
        $installationSucceeded = $installActionDispatcher->dispatch(
            $this->request->getArguments(),
            [
                'integrityCheck' => !$skipIntegrityCheck,
                'forceInstall' => $force,
                'interactive' => $isInteractive,
                'extensionSetup' => !$skipExtensionSetup && CompatibilityScripts::isComposerMode(),
            ],
            $installStepsConfig
        );

        if (!$installationSucceeded) {
            $this->quit(2);
        }

        $this->outputLine();
        $this->outputLine('<i>Successfully installed TYPO3 CMS!</i>');
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
     * <b>Example:</b> <code>%command.full_name%</code>
     *
     * @param array $frameworkExtensions TYPO3 system extensions that should be marked as active. Extension keys separated by comma.
     * @param array $excludedExtensions Extensions which should stay inactive. This does not affect provided framework extensions or framework extensions that are required or part as minimal usable system.
     * @param bool $activateDefault (DEPRECATED) If true, <code>typo3/cms</code> extensions that are marked as TYPO3 factory default, will be activated, even if not in the list of configured active framework extensions.
     */
    public function generatePackageStatesCommand(array $frameworkExtensions = [], array $excludedExtensions = [], $activateDefault = false)
    {
        if ($activateDefault && CompatibilityScripts::isComposerMode()) {
            // @deprecated for composer usage in 5.0 will be removed with 6.0
            $this->outputLine('<warning>Using --activate-default is deprecated in composer managed TYPO3 installations.</warning>');
            $this->outputLine('<warning>Instead of requiring typo3/cms in your project, you should consider only requiring individual packages you need.</warning>');
        }
        $frameworkExtensions = $frameworkExtensions ?: explode(',', (string)getenv('TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS'));
        $packageStatesGenerator = new PackageStatesGenerator($this->packageManager);
        $activatedExtensions = $packageStatesGenerator->generate($frameworkExtensions, $excludedExtensions, $activateDefault);

        try {
            // Make sure file caches are empty after generating package states file
            CommandDispatcher::createFromCommandRun()->executeCommand('cache:flush', ['--files-only']);
        } catch (FailedSubProcessCommandException $e) {
            // Ignore errors here.
            // They might be triggered from extensions accessing db or having other things
            // broken in ext_tables or ext_localconf
            // In such case we cannot do much about it other than ignoring it for
            // generating packages states
        }

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
     * Setup TYPO3 with extensions if possible
     *
     * This command tries up all TYPO3 extensions, but quits gracefully if this is not possible.
     * This can be used in <code>composer.json</code> scripts to ensure that extensions
     * are always set up correctly after a composer run on development systems,
     * but does not fail on packaging for deployment where no database connection is available.
     *
     * Besides that, it can be used for a first deploy of a TYPO3 instance in a new environment,
     * but also works for subsequent deployments.
     *
     * @see typo3_console:extension:setupactive
     */
    public function extensionSetupIfPossibleCommand()
    {
        $commandDispatcher = CommandDispatcher::createFromCommandRun();
        try {
            $this->outputLine($commandDispatcher->executeCommand('database:updateschema'));
            $this->outputLine($commandDispatcher->executeCommand('cache:flush'));
            $this->outputLine($commandDispatcher->executeCommand('extension:setupactive'));
        } catch (FailedSubProcessCommandException $e) {
            $this->outputLine('<warning>Extension setup skipped.</warning>');
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
    public function databaseConnectCommand($databaseUserName = '', $databaseUserPassword = '', $databaseHostName = '127.0.0.1', $databasePort = '3306', $databaseSocket = '')
    {
        $this->executeActionWithArguments('databaseConnect', ['host' => $databaseHostName, 'port' => $databasePort, 'username' => $databaseUserName, 'password' => $databaseUserPassword, 'socket' => $databaseSocket, 'driver' => 'mysqli']);
    }

    /**
     * Select database
     *
     * Select a database by name
     *
     * @param string $databaseName Name of the database
     * @param bool $useExistingDatabase Use already existing database?
     * @Definition\Option(name="databaseName")
     * @internal
     */
    public function databaseSelectCommand($databaseName, $useExistingDatabase = false)
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
     * @Definition\Option(name="adminUserName")
     * @Definition\Option(name="adminPassword")
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
     * - site (which creates an empty root page and setup)
     * - no (which unsurprisingly does nothing at all)
     *
     * In non composer mode the following option is also available:
     * - dist (which loads a list of distributions you can install)
     *
     * @param string $siteSetupType Specify the setup type: Create empty root page (site), Do nothing (no)
     * @internal
     */
    public function defaultConfigurationCommand($siteSetupType = 'no')
    {
        switch ($siteSetupType) {
            case 'site':
            case 'createsite':
                $arguments = ['sitesetup' => 'createsite'];
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
}
