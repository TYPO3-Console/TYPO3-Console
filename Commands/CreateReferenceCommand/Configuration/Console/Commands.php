<?php
return [
    'commands' => [
        'commandreference:render' => [
            'class' => \Typo3Console\CreateReferenceCommand\Command\CommandReferenceRenderCommand::class,
        ],
    ],
    'runLevels' => [
        'typo3-console/create-reference-command:commandreference:render' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_COMPILE,
    ],
    'bootingSteps' => [
    ],
];
