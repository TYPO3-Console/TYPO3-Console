<?php
declare(strict_types=1);

return [
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
    'upgrade:list' => [
        'vendor' => 'typo3_console',
        'class' => \Helhum\Typo3Console\Command\Upgrade\UpgradeListCommand::class,
        'replace' => [
            \TYPO3\CMS\Install\Command\UpgradeWizardListCommand::class,
        ],
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_FULL,
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
