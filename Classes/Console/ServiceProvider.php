<?php
declare(strict_types=1);
namespace Helhum\Typo3Console;

use Helhum\ConfigLoader\Processor\PlaceholderValue;
use Helhum\Typo3Console\Command\CommandApplication;
use Helhum\Typo3Console\Command\Configuration\ConfigurationRemoveCommand;
use Helhum\Typo3Console\Command\Configuration\ConfigurationSetCommand;
use Helhum\Typo3Console\Command\Configuration\ConfigurationShowLocalCommand;
use Helhum\Typo3Console\Command\Database\DatabaseExportCommand;
use Helhum\Typo3Console\Command\Database\DatabaseImportCommand;
use Helhum\Typo3Console\Command\Database\DatabaseUpdateSchemaCommand;
use Helhum\Typo3Console\Command\Frontend\FrontendAssetUrlCommand;
use Helhum\Typo3Console\Command\Install\InstallActionNeedsExecutionCommand;
use Helhum\Typo3Console\Command\Install\InstallDatabaseConnectCommand;
use Helhum\Typo3Console\Command\Install\InstallDatabaseDataCommand;
use Helhum\Typo3Console\Command\Install\InstallDatabaseSelectCommand;
use Helhum\Typo3Console\Command\Install\InstallDefaultConfigurationCommand;
use Helhum\Typo3Console\Command\Install\InstallEnvironmentAndFoldersCommand;
use Helhum\Typo3Console\Command\Install\InstallExtensionSetupIfPossibleCommand;
use Helhum\Typo3Console\Command\Install\InstallFixFolderStructureCommand;
use Helhum\Typo3Console\Command\Install\InstallSetupCommand;
use Helhum\Typo3Console\Command\InstallTool\LockInstallToolCommand;
use Helhum\Typo3Console\Command\InstallTool\UnlockInstallToolCommand;
use Helhum\Typo3Console\Database\Configuration\ConnectionConfiguration;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Adapter\EventDispatcherAdapter as SymfonyEventDispatcherAdapter;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Console\CommandApplication as CoreCommandApplication;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\SymfonyPsrEventDispatcherAdapter\EventDispatcherAdapter as LegacySymfonyEventDispatcherAdapter;

class ServiceProvider extends AbstractServiceProvider
{
    protected static function getPackagePath(): string
    {
        return __DIR__ . '/../../';
    }

    protected static function getPackageName(): string
    {
        return 'helhum/typo3-console';
    }

    public function getFactories(): array
    {
        if (!class_exists(PlaceholderValue::class) && !Environment::isComposerMode()) {
            require __DIR__ . '/../../Resources/Private/ExtensionArtifacts/Libraries/autoload.php';
        }

        return [
            ConfigurationRemoveCommand::class => [ static::class, 'getConfigurationRemoveCommand' ],
            ConfigurationSetCommand::class => [ static::class, 'getConfigurationSetCommand' ],
            ConfigurationShowLocalCommand::class => [ static::class, 'getConfigurationShowLocalCommand' ],
            DatabaseExportCommand::class => [ static::class, 'getDatabaseExportCommand' ],
            DatabaseImportCommand::class => [ static::class, 'getDatabaseImportCommand' ],
            DatabaseUpdateSchemaCommand::class => [ static::class, 'getDatabaseUpdateSchemaCommand' ],
            InstallSetupCommand::class => [ static::class, 'getInstallSetupCommand' ],
            InstallFixFolderStructureCommand::class => [ static::class, 'getInstallFixFolderStructureCommand' ],
            InstallExtensionSetupIfPossibleCommand::class => [ static::class, 'getInstallExtensionSetupIfPossibleCommand' ],
            InstallEnvironmentAndFoldersCommand::class => [ static::class, 'getInstallEnvironmentAndFoldersCommand' ],
            InstallDatabaseConnectCommand::class => [ static::class, 'getInstallDatabaseConnectCommand' ],
            InstallDatabaseDataCommand::class => [ static::class, 'getInstallDatabaseDataCommand' ],
            InstallDatabaseSelectCommand::class => [ static::class, 'getInstallDatabaseSelectCommand' ],
            InstallDefaultConfigurationCommand::class => [ static::class, 'getInstallDefaultConfigurationCommand' ],
            InstallActionNeedsExecutionCommand::class => [ static::class, 'getInstallActionNeedsExecutionCommand' ],
            LockInstallToolCommand::class => [ static::class, 'getLockInstallToolCommand' ],
            UnlockInstallToolCommand::class => [ static::class, 'getUnlockInstallToolCommand' ],
            FrontendAssetUrlCommand::class => [ static::class, 'getFrontendAssetUrlCommand' ],
        ];
    }

    public function getExtensions(): array
    {
        return [
            CommandRegistry::class => [ static::class, 'configureCommands' ],
            CoreCommandApplication::class => [ static::class, 'configureCoreCommandApplication' ],
        ] + parent::getExtensions();
    }

    public static function getConfigurationRemoveCommand(ContainerInterface $container): ConfigurationRemoveCommand
    {
        return new ConfigurationRemoveCommand(self::applicationIsReady($container));
    }

    public static function getConfigurationSetCommand(ContainerInterface $container): ConfigurationSetCommand
    {
        return new ConfigurationSetCommand(self::applicationIsReady($container));
    }

    public static function getConfigurationShowLocalCommand(ContainerInterface $container): ConfigurationShowLocalCommand
    {
        return new ConfigurationShowLocalCommand(self::applicationIsReady($container));
    }

    public static function getDatabaseExportCommand(ContainerInterface $container): DatabaseExportCommand
    {
        return new DatabaseExportCommand(
            self::applicationIsReady($container),
            new ConnectionConfiguration()
        );
    }

    public static function getDatabaseImportCommand(ContainerInterface $container): DatabaseImportCommand
    {
        return new DatabaseImportCommand(
            self::applicationIsReady($container),
            new ConnectionConfiguration()
        );
    }

    public static function getDatabaseUpdateSchemaCommand(ContainerInterface $container): DatabaseUpdateSchemaCommand
    {
        return new DatabaseUpdateSchemaCommand(
            $container->get(BootService::class)
        );
    }

    public static function getInstallSetupCommand(): InstallSetupCommand
    {
        return new InstallSetupCommand('install:setup');
    }

    public static function getInstallFixFolderStructureCommand(): InstallFixFolderStructureCommand
    {
        return new InstallFixFolderStructureCommand('install:fixfolderstructure');
    }

    public static function getInstallExtensionSetupIfPossibleCommand(): InstallExtensionSetupIfPossibleCommand
    {
        return new InstallExtensionSetupIfPossibleCommand('install:extensionsetupifpossible');
    }

    public static function getInstallEnvironmentAndFoldersCommand(): InstallEnvironmentAndFoldersCommand
    {
        return new InstallEnvironmentAndFoldersCommand('install:environmentandfolders');
    }

    public static function getInstallDatabaseConnectCommand(): InstallDatabaseConnectCommand
    {
        return new InstallDatabaseConnectCommand('install:databaseconnect');
    }

    public static function getInstallDatabaseDataCommand(ContainerInterface $container): InstallDatabaseDataCommand
    {
        return new InstallDatabaseDataCommand($container->get(BootService::class));
    }

    public static function getInstallDatabaseSelectCommand(): InstallDatabaseSelectCommand
    {
        return new InstallDatabaseSelectCommand('install:databaseselect');
    }

    public static function getInstallDefaultConfigurationCommand(ContainerInterface $container): InstallDefaultConfigurationCommand
    {
        return new InstallDefaultConfigurationCommand($container->get(BootService::class));
    }

    public static function getInstallActionNeedsExecutionCommand(): InstallActionNeedsExecutionCommand
    {
        return new InstallActionNeedsExecutionCommand('install:actionneedsexecution');
    }

    public static function getLockInstallToolCommand(): LockInstallToolCommand
    {
        return new LockInstallToolCommand('install:lock');
    }

    public static function getUnlockInstallToolCommand(): UnlockInstallToolCommand
    {
        return new UnlockInstallToolCommand('install:unlock');
    }

    public static function getFrontendAssetUrlCommand(ContainerInterface $container): FrontendAssetUrlCommand
    {
        return new FrontendAssetUrlCommand($container->get(PackageManager::class));
    }

    public static function configureCoreCommandApplication(ContainerInterface $container, CoreCommandApplication $commandApplication): CoreCommandApplication
    {
        $commandRegistry = $container->get(CommandRegistry::class);
        $application = new Application();
        $application->setAutoExit(false);
        $application->setDispatcher(self::getSymfonyEventDispatcher($container));
        $application->setCommandLoader($commandRegistry);
        // Replace default list command with TYPO3 override
        $application->add($commandRegistry->get('list'));
        CommandApplication::overrideApplication($commandApplication, $application);

        return $commandApplication;
    }

    public static function configureCommands(ContainerInterface $container, CommandRegistry $commandRegistry): CommandRegistry
    {
        $commandRegistry->addLazyCommand('configuration:remove', ConfigurationRemoveCommand::class, 'Remove configuration value');
        $commandRegistry->addLazyCommand('configuration:set', ConfigurationSetCommand::class, 'Set configuration value');
        $commandRegistry->addLazyCommand('configuration:showlocal', ConfigurationShowLocalCommand::class, 'Show local configuration value');
        $commandRegistry->addLazyCommand('database:export', DatabaseExportCommand::class, 'Export database to stdout');
        $commandRegistry->addLazyCommand('database:import', DatabaseImportCommand::class, 'Import mysql queries from stdin');
        $commandRegistry->addLazyCommand('database:updateschema', DatabaseUpdateSchemaCommand::class, 'Update database schema (TYPO3 Database Compare)');
        $commandRegistry->addLazyCommand('install:setup', InstallSetupCommand::class, 'TYPO3 Setup');
        $commandRegistry->addLazyCommand('install:fixfolderstructure', InstallFixFolderStructureCommand::class, 'Fix folder structure');
        $commandRegistry->addLazyCommand('install:extensionsetupifpossible', InstallExtensionSetupIfPossibleCommand::class, 'Fix folder structure');
        $commandRegistry->addLazyCommand('install:environmentandfolders', InstallEnvironmentAndFoldersCommand::class, 'Check environment / create folders', true);
        $commandRegistry->addLazyCommand('install:databaseconnect', InstallDatabaseConnectCommand::class, 'Connect to database', true);
        $commandRegistry->addLazyCommand('install:databasedata', InstallDatabaseDataCommand::class, 'Add database data', true);
        $commandRegistry->addLazyCommand('install:databaseselect', InstallDatabaseSelectCommand::class, 'Select database', true);
        $commandRegistry->addLazyCommand('install:defaultconfiguration', InstallDefaultConfigurationCommand::class, 'Write default configuration', true);
        $commandRegistry->addLazyCommand('install:actionneedsexecution', InstallActionNeedsExecutionCommand::class, 'Calls needs execution on the given action and returns the result', true);
        $commandRegistry->addLazyCommand('install:lock', LockInstallToolCommand::class, 'Lock Install Tool');
        $commandRegistry->addLazyCommand('install:unlock', UnlockInstallToolCommand::class, 'Unlock Install Tool');
        $commandRegistry->addLazyCommand('frontend:asseturl', FrontendAssetUrlCommand::class, 'Show asset URL for TYPO3 extension(s)');

        return $commandRegistry;
    }

    private static function getSymfonyEventDispatcher(ContainerInterface $container): LegacySymfonyEventDispatcherAdapter | SymfonyEventDispatcherAdapter
    {
        if (class_exists(LegacySymfonyEventDispatcherAdapter::class)) {
            // @deprecated can be removed once  TYPO3 11 support is removed
            return $container->get(LegacySymfonyEventDispatcherAdapter::class);
        }

        return $container->get(SymfonyEventDispatcherAdapter::class);
    }

    private static function applicationIsReady(ContainerInterface $container): bool
    {
        return Bootstrap::checkIfEssentialConfigurationExists($container->get(ConfigurationManager::class));
    }
}
