<?php
declare(strict_types=1);

return [
    'convert-command-controller' => [
        'class' => \Typo3Console\ConvertCommandControllerCommand\Command\ConvertCommandControllerCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_FULL,
        'vendor' => 'typo3_console',
    ],
];
