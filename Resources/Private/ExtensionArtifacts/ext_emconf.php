<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 Console',
    'description' => 'A reliable and powerful command line interface for TYPO3 CMS',
    'category' => 'cli',
    'state' => 'stable',
    'modify_tables' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Helmut Hummel',
    'author_email' => 'info@helhum.io',
    'author_company' => 'helhum.io',
    'version' => '8.0.0',
    'constraints' => [
        'depends' => [
            'php' => '7.4.1-8.99.99',
            'typo3' => '11.5.3-12.5.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
