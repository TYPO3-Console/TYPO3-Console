<?php
declare(strict_types=1);
namespace Helhum\Typo3Console;

use Helhum\Typo3Console\Command\Database\DatabaseUpdateSchemaCommand;
use Helhum\Typo3Console\Command\Install\InstallActionNeedsExecutionCommand;
use Helhum\Typo3Console\Command\Install\InstallDatabaseConnectCommand;
use Helhum\Typo3Console\Command\Install\InstallDatabaseDataCommand;
use Helhum\Typo3Console\Command\Install\InstallDatabaseSelectCommand;
use Helhum\Typo3Console\Command\Install\InstallEnvironmentAndFoldersCommand;
use Helhum\Typo3Console\Command\Install\InstallExtensionSetupIfPossibleCommand;
use Helhum\Typo3Console\Command\Install\InstallFixFolderStructureCommand;
use Helhum\Typo3Console\Command\Install\InstallSetupCommand;
use Helhum\Typo3Console\Command\InstallTool\LockInstallToolCommand;
use Helhum\Typo3Console\Command\InstallTool\UnlockInstallToolCommand;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected static function getPackagePath(): string
    {
        return __DIR__ . '/../../';
    }

    public function getFactories(): array
    {
        return [
            DatabaseUpdateSchemaCommand::class => [ static::class, 'getDatabaseUpdateSchemaCommand' ],
            InstallSetupCommand::class => [ static::class, 'getInstallSetupCommand' ],
            InstallFixFolderStructureCommand::class => [ static::class, 'getInstallFixFolderStructureCommand' ],
            InstallExtensionSetupIfPossibleCommand::class => [ static::class, 'getInstallExtensionSetupIfPossibleCommand' ],
            InstallEnvironmentAndFoldersCommand::class => [ static::class, 'getInstallEnvironmentAndFoldersCommand' ],
            InstallDatabaseConnectCommand::class => [ static::class, 'getInstallDatabaseConnectCommand' ],
            InstallDatabaseDataCommand::class => [ static::class, 'getInstallDatabaseDataCommand' ],
            InstallDatabaseSelectCommand::class => [ static::class, 'getInstallDatabaseSelectCommand' ],
            InstallActionNeedsExecutionCommand::class => [ static::class, 'getInstallActionNeedsExecutionCommand' ],
            LockInstallToolCommand::class => [ static::class, 'getLockInstallToolCommand' ],
            UnlockInstallToolCommand::class => [ static::class, 'getUnlockInstallToolCommand' ],
        ];
    }

    public function getExtensions(): array
    {
        return [
            CommandRegistry::class => [ static::class, 'configureCommands' ],
        ] + parent::getExtensions();
    }

    public static function getDatabaseUpdateSchemaCommand(ContainerInterface $container): DatabaseUpdateSchemaCommand
    {
        return new DatabaseUpdateSchemaCommand($container->get(BootService::class));
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

    public static function configureCommands(ContainerInterface $container, CommandRegistry $commandRegistry): CommandRegistry
    {
        $commandRegistry->addLazyCommand('database:updateschema', DatabaseUpdateSchemaCommand::class, 'Update database schema (TYPO3 Database Compare)');
        $commandRegistry->addLazyCommand('install:setup', InstallSetupCommand::class, 'TYPO3 Setup');
        $commandRegistry->addLazyCommand('install:fixfolderstructure', InstallFixFolderStructureCommand::class, 'Fix folder structure');
        $commandRegistry->addLazyCommand('install:extensionsetupifpossible', InstallExtensionSetupIfPossibleCommand::class, 'Fix folder structure');
        $commandRegistry->addLazyCommand('install:environmentandfolders', InstallEnvironmentAndFoldersCommand::class, 'Check environment / create folders');
        $commandRegistry->addLazyCommand('install:databaseconnect', InstallDatabaseConnectCommand::class, 'Connect to database');
        $commandRegistry->addLazyCommand('install:databasedata', InstallDatabaseDataCommand::class, 'Add database data');
        $commandRegistry->addLazyCommand('install:databaseselect', InstallDatabaseSelectCommand::class, 'Select database');
        $commandRegistry->addLazyCommand('install:actionneedsexecution', InstallActionNeedsExecutionCommand::class, 'Calls needs execution on the given action and returns the result');
        $commandRegistry->addLazyCommand('install:lock', LockInstallToolCommand::class, 'Lock Install Tool');
        $commandRegistry->addLazyCommand('install:unlock', UnlockInstallToolCommand::class, 'Unlock Install Tool');

        return $commandRegistry;
    }
}
