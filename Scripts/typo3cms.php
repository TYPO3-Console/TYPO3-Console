<?php
call_user_func(function () {
    // The following checks are safeguards to identify issues with outdated typo3cms binaries
    // Check again if php requirement is satisfied.
    if (version_compare(PHP_VERSION, '5.5.0', '<')) {
        echo 'This version of TYPO3 Console requires PHP 5.5.0 or above!' . PHP_EOL;
        exit(1);
    }
    // Check again if path is set correctly.
    if (!getenv('TYPO3_PATH_WEB')) {
        echo 'Could not find TYPO3 installation root path! Make sure TYPO3_PATH_WEB environment variable is set correctly and your typo3cms binary is up to date!' . PHP_EOL;
        exit(1);
    }

    define('PATH_site', strtr(getenv('TYPO3_PATH_WEB'), '\\', '/') . '/');
    define('PATH_thisScript', realpath(PATH_site . 'typo3/cli_dispatch.phpsh'));

    $classLoader = require_once realpath(PATH_site . 'typo3/../') . '/vendor/autoload.php';

    require __DIR__ . '/../Classes/Core/ConsoleBootstrap.php';
    $bootstrap = new \Helhum\Typo3Console\Core\ConsoleBootstrap(getenv('TYPO3_CONTEXT') ?: 'Production');
    $bootstrap->run($classLoader);
});
