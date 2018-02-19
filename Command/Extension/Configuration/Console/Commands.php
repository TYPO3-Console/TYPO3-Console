<?php
return [
    'commands' => [
        'extension:dumpactive' => [
            'vendor' => 'typo3_console',
            'class' => \Typo3Console\Command\Extension\DumpActiveCommand::class,
            'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
            'aliases' => [
                'install:generatepackagestates',
                'typo3_console:install:generatepackagestates',
            ],
        ],
    ],
];
