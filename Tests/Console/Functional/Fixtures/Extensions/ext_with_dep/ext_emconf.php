<?php
$EM_CONF[$_EXTKEY] = [
  'title' => 'Test Extension with dependency',
  'description' => 'Extension fixture to prove generating package states working correctly',
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
      'typo3' => '4.5.0-',
      'ext_no_dep' => '',
    ],
    'conflicts' => [
    ],
    'suggests' => [
    ],
  ],
];
