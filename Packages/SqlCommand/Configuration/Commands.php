<?php
declare(strict_types=1);

return [
    'sql' => [
        'class' => \Typo3Console\SQLCommand\Command\SqlCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
        'vendor' => 'typo3_console',
    ],
];
