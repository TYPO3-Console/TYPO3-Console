<?php
declare(strict_types=1);

return [
    'backend:createadmin' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Backend\CreateBackendAdminUserCommand::class,
        'schedulable' => false,
    ],
    'backend:lock' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Backend\LockBackendCommand::class,
    ],
    'backend:lockforeditors' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Backend\LockBackendForEditorsCommand::class,
        'schedulable' => false,
    ],
    'backend:unlock' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Backend\UnlockBackendCommand::class,
    ],
    'backend:unlockforeditors' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Backend\UnlockBackendForEditorsCommand::class,
        'schedulable' => false,
    ],
    'cache:flush' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Cache\CacheFlushCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'cache:flushgroups' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Cache\CacheFlushGroupsCommand::class,
        'schedulable' => false,
    ],
    'cache:flushtags' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Cache\CacheFlushTagsCommand::class,
        'schedulable' => false,
    ],
    'cache:listgroups' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Cache\CacheListGroupsCommand::class,
        'schedulable' => false,
    ],
    'configuration:remove' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Configuration\ConfigurationRemoveCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
    ],
    'configuration:set' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Configuration\ConfigurationSetCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
    ],
    'configuration:show' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Configuration\ConfigurationShowCommand::class,
        'schedulable' => false,
    ],
    'configuration:showactive' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Configuration\ConfigurationShowActiveCommand::class,
        'schedulable' => false,
    ],
    'configuration:showlocal' => [
        'class' => \Helhum\Typo3Console\Command\Configuration\ConfigurationShowLocalCommand::class,
        'schedulable' => false,
        'vendor' => 'typo3_console',
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
    ],
    'database:export' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Database\DatabaseExportCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
    ],
    'database:import' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Database\DatabaseImportCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
    ],
    'database:updateschema' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Database\DatabaseUpdateSchemaCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
        'bootingSteps' => [
            'helhum.typo3console:persistence',
        ],
    ],
    'dumpautoload' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Extension\DumpAutoloadCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
        'replace' => [
            'core:dumpautoload',
        ],
    ],
    'extension:activate' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Extension\ExtensionActivateCommand::class,
        'schedulable' => false,
        'replace' => [
            'extensionmanager:extension:activate',
        ],
        'aliases' => [
            'extension:install',
        ],
    ],
    'extension:deactivate' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Extension\ExtensionDeactivateCommand::class,
        'schedulable' => false,
        'replace' => [
            'extensionmanager:extension:deactivate',
        ],
        'aliases' => [
            'extension:uninstall',
        ],
    ],
    'extension:list' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Extension\ExtensionListCommand::class,
        'schedulable' => false,
    ],
    'extension:setup' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Extension\ExtensionSetupCommand::class,
        'schedulable' => false,
    ],
    'extension:setupactive' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Extension\ExtensionSetupActiveCommand::class,
        'schedulable' => false,
    ],
    'frontend:request' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Frontend\FrontendRequestCommand::class,
        'schedulable' => false,
    ],
    'install:setup' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallSetupCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'install:generatepackagestates' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallGeneratePackageStatesCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'install:fixfolderstructure' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallFixFolderStructureCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'install:extensionsetupifpossible' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallExtensionSetupIfPossibleCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'install:environmentandfolders' => [
        'class' => \Helhum\Typo3Console\Command\Install\InstallEnvironmentAndFoldersCommand::class,
        'vendor' => 'typo3_console',
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'install:databaseconnect' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallDatabaseConnectCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'install:databaseselect' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallDatabaseSelectCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'install:databasedata' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallDatabaseDataCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
        'bootingSteps' => [
            'helhum.typo3console:persistence',
        ],
    ],
    'install:defaultconfiguration' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallDefaultConfigurationCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
        'bootingSteps' => [
            'helhum.typo3console:persistence',
        ],
    ],
    'install:actionneedsexecution' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallActionNeedsExecutionCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'upgrade:all' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Upgrade\UpgradeAllCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
        'replace' => [
            'install:upgrade:run',
        ],
    ],
    'upgrade:checkextensionconstraints' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Upgrade\UpgradeCheckExtensionConstraintsCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'upgrade:list' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Upgrade\UpgradeListCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
        'replace' => [
            'install:upgrade:list',
        ],
        'aliases' => [
            'install:upgrade:list',
        ],
    ],
    'upgrade:wizard' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Upgrade\UpgradeWizardCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'upgrade:subprocess' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Upgrade\UpgradeSubProcessCommand::class,
        'schedulable' => false,
    ],
    'upgrade:checkextensioncompatibility' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Upgrade\UpgradeCheckExtensionCompatibilityCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
];
