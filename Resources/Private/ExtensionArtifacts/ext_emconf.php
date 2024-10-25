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
    'version' => '8.2.1',
    'constraints' => [
        'depends' => [
            'php' => '8.1.0-8.99.99',
            'typo3' => '11.5.26-13.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
