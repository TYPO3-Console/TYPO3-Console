<?php
call_user_func(function () {
    if (getenv('TYPO3_PATH_WEB')) {
        $webRoot = getenv('TYPO3_PATH_WEB');
    } else {
        echo "Could not find TYPO3 installation root path! Make sure TYPO3_PATH_WEB environment variable is set correctly and your typo3cms binary is up to date!\n";
        exit(1);
    }

    define('PATH_site', strtr($webRoot, '\\', '/') . '/');
    define('PATH_thisScript', realpath(PATH_site . 'typo3/cli_dispatch.phpsh'));

    $classLoader = require_once realpath(PATH_site . 'typo3/../') . '/vendor/autoload.php';

    require __DIR__ . '/../Classes/Core/ConsoleBootstrap.php';
    $bootstrap = new \Helhum\Typo3Console\Core\ConsoleBootstrap(getenv('TYPO3_CONTEXT') ?: 'Production');
    $bootstrap->run($classLoader);
});
