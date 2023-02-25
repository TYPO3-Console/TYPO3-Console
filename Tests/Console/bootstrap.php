<?php
declare(strict_types=1);

\TYPO3\CMS\Core\Core\Environment::initialize(
    new \TYPO3\CMS\Core\Core\ApplicationContext('Testing'),
    true,
    false,
    getenv('TYPO3_PATH_APP'),
    getenv('TYPO3_PATH_WEB'),
    getenv('TYPO3_PATH_APP') . '/var',
    getenv('TYPO3_PATH_APP') . '/typo3conf',
    getenv('TYPO3_PATH_WEB') . '/index.php',
    'NIX'
);
