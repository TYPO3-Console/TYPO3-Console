<?php
declare(strict_types=1);

use Helhum\Typo3Console\Tests\Functional\Fixtures\Extensions\ext_command\src\ExtAlias;
use Helhum\Typo3Console\Tests\Functional\Fixtures\Extensions\ext_command\src\ExtCommand;

return [
    'ext:command' => [
        'class' => ExtCommand::class,
    ],
    'ext:alias' => [
        'class' => ExtAlias::class,
        'aliases' => [
            'ext:alias1',
            'ext2:alias',
        ],
    ],
    'extension:list' => [
        'class' => ExtCommand::class,
        'runLevel' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'extension:activate' => [
        'class' => ExtCommand::class,
        'vendor' => 'ext_bla',
    ],
];
