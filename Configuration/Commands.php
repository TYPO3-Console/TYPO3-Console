<?php
declare(strict_types=1);

return [
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
