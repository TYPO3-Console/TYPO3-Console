<?php
declare(strict_types=1);

return [
    'ext:command' => [
        'class' => \TYPO3Console\ExtCommand\ExtCommand::class,
    ],
    'ext:alias' => [
        'class' => \TYPO3Console\ExtCommand\ExtAlias::class,
        'aliases' => [
            'ext:alias1',
            'ext2:alias',
        ],
    ],
    'extension:list' => [
        'class' => \TYPO3Console\ExtCommand\ExtCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'extension:activate' => [
        'class' => \TYPO3Console\ExtCommand\ExtCommand::class,
        'vendor' => 'ext_bla',
    ],
];
