<?php
declare(strict_types=1);

return [
    'backend:createadmin' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Backend\CreateBackendAdminUserCommand::class,
    ],
    'backend:lockforeditors' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Backend\LockBackendForEditorsCommand::class,
    ],
    'backend:unlockforeditors' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Backend\UnlockBackendForEditorsCommand::class,
    ],
    'cache:flushtags' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Cache\CacheFlushTagsCommand::class,
    ],
    'cache:listgroups' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Cache\CacheListGroupsCommand::class,
    ],
    'configuration:remove' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Configuration\ConfigurationRemoveCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
    ],
    'configuration:set' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Configuration\ConfigurationSetCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
    ],
    'configuration:show' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Configuration\ConfigurationShowCommand::class,
    ],
    'configuration:showactive' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Configuration\ConfigurationShowActiveCommand::class,
    ],
    'configuration:showlocal' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Configuration\ConfigurationShowLocalCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
    ],
    'database:export' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Database\DatabaseExportCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
    ],
    'database:import' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Database\DatabaseImportCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
    ],
    'database:updateschema' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Database\DatabaseUpdateSchemaCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
        'bootingSteps' => [
            'helhum.typo3console:persistence',
        ],
    ],
    'frontend:request' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Frontend\FrontendRequestCommand::class,
    ],
    'install:setup' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallSetupCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'install:generatepackagestates' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallGeneratePackageStatesCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'install:fixfolderstructure' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallFixFolderStructureCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'install:extensionsetupifpossible' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallExtensionSetupIfPossibleCommand::class,
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
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'install:databaseselect' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallDatabaseSelectCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'install:databasedata' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallDatabaseDataCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
        'bootingSteps' => [
            'helhum.typo3console:persistence',
        ],
    ],
    'install:defaultconfiguration' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallDefaultConfigurationCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL,
        'bootingSteps' => [
            'helhum.typo3console:persistence',
        ],
    ],
    'install:actionneedsexecution' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Install\InstallActionNeedsExecutionCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'install:lock' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\InstallTool\LockInstallToolCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'install:unlock' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\InstallTool\UnlockInstallToolCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'upgrade:checkextensioncompatibility' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Upgrade\UpgradeCheckExtensionCompatibilityCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'upgrade:checkextensionconstraints' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Upgrade\UpgradeCheckExtensionConstraintsCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'upgrade:list' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Upgrade\UpgradeListCommand::class,
        'replace' => [
            \TYPO3\CMS\Install\Command\UpgradeWizardListCommand::class,
        ],
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_FULL,
    ],
    'upgrade:prepare' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Upgrade\UpgradePrepareCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'upgrade:run' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Upgrade\UpgradeRunCommand::class,
        'replace' => [
            \TYPO3\CMS\Install\Command\UpgradeWizardRunCommand::class,
        ],
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_FULL,
    ],
];
