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
  'version' => '6.3.1',
  'constraints' => [
    'depends' => [
      'php' => '7.2.0-7.4.99',
      'typo3' => '10.4.0-10.4.99',
      'extbase' => '10.4.0-10.4.99',
      'extensionmanager' => '10.4.0-10.4.99',
      'fluid' => '10.4.0-10.4.99',
      'install' => '10.4.0-10.4.99',
    ],
    'conflicts' => [
        'dbal' => '',
    ],
    'suggests' => [
    ],
  ],
];
