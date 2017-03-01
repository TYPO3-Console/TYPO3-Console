<?php
return [
    'controllers' => [
        \Helhum\Typo3Console\Command\HelpCommandController::class,
        \Helhum\Typo3Console\Command\CacheCommandController::class,
        \Helhum\Typo3Console\Command\BackendCommandController::class,
        \Helhum\Typo3Console\Command\SchedulerCommandController::class,
        \Helhum\Typo3Console\Command\CleanupCommandController::class,
        \Helhum\Typo3Console\Command\DocumentationCommandController::class,
        \Helhum\Typo3Console\Command\InstallCommandController::class,
        \Helhum\Typo3Console\Command\DatabaseCommandController::class,
        \Helhum\Typo3Console\Command\ConfigurationCommandController::class,
        \Helhum\Typo3Console\Command\FrontendCommandController::class,
        \Helhum\Typo3Console\Command\ExtensionCommandController::class,
    ],
    'runLevels' => [
        'typo3_console:install:databasedata' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL ,
        'typo3_console:install:defaultconfiguration' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL ,
        'typo3_console:install:*' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE ,
        'typo3_console:cache:flush' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE ,
        'typo3_console:commandreference:render' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL ,
        'typo3_console:configuration:*' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL ,
        'typo3_console:database:import' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL ,
        'typo3_console:database:updateschema' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL ,
        'typo3_console:extension:*' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_FULL ,
        'typo3_console:extension:removeinactive' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE ,
    ],
    'bootingSteps' => [
        'typo3_console:install:databasedata' => ['helhum.typo3console:database'],
        'typo3_console:install:defaultconfiguration' => ['helhum.typo3console:database'],
        'typo3_console:cache:flush' => ['helhum.typo3console:database'],
        'typo3_console:database:updateschema' => [
            'helhum.typo3console:database',
            'helhum.typo3console:persistence',
        ],
    ]
];
