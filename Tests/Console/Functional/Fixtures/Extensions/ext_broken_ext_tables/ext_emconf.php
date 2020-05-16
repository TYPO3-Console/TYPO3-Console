<?php
$EM_CONF[$_EXTKEY] = [
  'title' => 'Test Extension',
  'description' => 'Extension fixture to prove extension compat check is working correctly',
  'category' => 'cli',
  'state' => 'stable',
  'modify_tables' => '',
  'clearCacheOnLoad' => 0,
  'author' => 'Helmut Hummel',
  'author_email' => 'info@helhum.io',
  'author_company' => 'helhum.io',
  'version' => '0.1.0',
  'constraints' => [
    'depends' => [
      'typo3' => '9.5.0-',
    ],
    'conflicts' => [
    ],
    'suggests' => [
    ],
  ],
];
