<?php
declare(strict_types=1);

return [
    '_dummy' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Mvc\Cli\Symfony\Command\DummyCommand::class,
        'schedulable' => false,
        'replace' => [
            'extbase:_core_command',
            'extbase:_extbase_help',
            'extbase:help:error',
            'typo3_console:_dummy',
        ],
    ],
    'backend:createadmin' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Backend\CreateBackendAdminUserCommand::class,
        'schedulable' => false,
    ],
    'backend:lock' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Backend\LockBackendCommand::class,
        'schedulable' => false,
        'replace' => [
            'backend:backend:lock',
        ],
    ],
    'backend:lockforeditors' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Backend\LockBackendForEditorsCommand::class,
        'schedulable' => false,
    ],
    'backend:unlock' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Backend\UnlockBackendCommand::class,
        'schedulable' => false,
        'replace' => [
            'backend:backend:unlock',
        ],
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
    'cleanup:updatereferenceindex' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Cleanup\UpdateReferenceIndexCommand::class,
        'schedulable' => false,
        'replace' => [
            'backend:referenceindex:update',
        ],
        'aliases' => [
            'backend:referenceindex:update',
            'referenceindex:update',
        ],
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
            'helhum.typo3console:database',
            'helhum.typo3console:persistence',
        ],
    ],
    'documentation:generatexsd' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Documentation\GenerateXsdCommand::class,
    ],
    'dumpautoload' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Extension\DumpAutoloadCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
        'replace' => [
            'core:dumpautoload',
            'extensionmanager:extension:dumpclassloadinginformation',
        ],
        'aliases' => [
            'extension:dumpautoload',
            'extension:dumpclassloadinginformation',
            'extensionmanager:extension:dumpclassloadinginformation',
        ],
    ],
    'extension:activate' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Extension\ExtensionActivateCommand::class,
        'schedulable' => false,
        'replace' => [
            'extensionmanager:extension:install',
            'extensionmanager:extension:activate',
        ],
        'aliases' => [
            'extension:install',
            'extensionmanager:extension:install',
            'extensionmanager:extension:activate',
        ],
    ],
    'extension:deactivate' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Extension\ExtensionDeactivateCommand::class,
        'schedulable' => false,
        'replace' => [
            'extensionmanager:extension:uninstall',
            'extensionmanager:extension:deactivate',
        ],
        'aliases' => [
            'extension:uninstall',
            'extensionmanager:extension:uninstall',
            'extensionmanager:extension:deactivate',
        ],
    ],
    'extension:list' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Extension\ExtensionListCommand::class,
        'schedulable' => false,
        'replace' => [
            'core:extension:list',
        ],
    ],
    'extension:removeinactive' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Extension\ExtensionRemoveInactiveCommand::class,
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
            'helhum.typo3console:database',
            'helhum.typo3console:persistence',
        ],
    ],
    'install:defaultconfiguration' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallDefaultConfigurationCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
        'bootingSteps' => [
            'helhum.typo3console:database',
            'helhum.typo3console:persistence',
        ],
    ],
    'install:actionneedsexecution' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallActionNeedsExecutionCommand::class,
        'schedulable' => false,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'scheduler:run' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Scheduler\SchedulerRunCommand::class,
        'schedulable' => false,
        'replace' => [
            'scheduler:scheduler:run',
        ],
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
