<?php
return array(
    'controllers' => array(
        \Helhum\Typo3Console\Command\CacheCommandController::class,
        \Helhum\Typo3Console\Command\BackendCommandController::class,
        \Helhum\Typo3Console\Command\SchedulerCommandController::class,
        \Helhum\Typo3Console\Command\CleanupCommandController::class,
        \Helhum\Typo3Console\Command\DocumentationCommandController::class,
        \Helhum\Typo3Console\Command\InstallCommandController::class,
        \Helhum\Typo3Console\Command\DatabaseCommandController::class,
        \Helhum\Typo3Console\Command\ConfigurationCommandController::class,
        \Helhum\Typo3Console\Command\FrontendCommandController::class,
        \Helhum\Typo3Console\Command\CommandReferenceCommandController::class,
    ),
    'runLevels' => array(
        'typo3_console:install:databasedata' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL ,
        'typo3_console:install:defaultconfiguration' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_MINIMAL ,
        'typo3_console:install:*' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE ,
        'typo3_console:cache:flush' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE ,
    ),
    'bootingSteps' => array(
        'typo3_console:install:databasedata' => array(
            'helhum.typo3console:database',
            'helhum.typo3console:enablecorecaches'
        ),
        'typo3_console:install:defaultconfiguration' => array('helhum.typo3console:database'),
        'typo3_console:cache:flush' => array('helhum.typo3console:database'),
    )
);
