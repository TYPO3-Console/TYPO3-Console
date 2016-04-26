<?php
call_user_func(function () {
    // Exit early if php requirement is not satisfied.
    if (version_compare(PHP_VERSION, '5.5.0', '<')) {
        echo 'This version of TYPO3 Console requires PHP 5.5.0 or above!' . PHP_EOL;
        if (defined('PHP_BINARY')) {
            echo 'Your PHP binary is located at: "' . PHP_BINARY . '",' . PHP_EOL;
            echo 'but its version is only: ' . PHP_VERSION . PHP_EOL;
        } else {
            echo 'Your PHP version is: ' . PHP_VERSION . PHP_EOL;
        }
        echo PHP_EOL . 'Please specify a suitable PHP cli binary before the typo3cms binary like that:' . PHP_EOL;
        echo '/path/to/php55-latest ' . $_SERVER['argv'][0] . PHP_EOL;
        exit(1);
    }

    if (getenv('TYPO3_PATH_WEB')) {
        // In case we are symlinked (like for travis tests),
        // we need to accept the location from the outside to find the autoload.php
        $typo3Root = getenv('TYPO3_PATH_WEB');
    } else {
        // Not symlinked (hopefully), so we can assume the docroot from the location of this file
        $typo3Root = __DIR__ . '/../../../..';
    }

    $classLoader = require_once realpath($typo3Root . '/typo3') . '/../vendor/autoload.php';

    if (!getenv('TYPO3_PATH_WEB')) {
        // Fallback to binary location in document root, if the plugin was not available (non composer mode)
        putenv('TYPO3_PATH_WEB=' . $typo3Root);
    }

    define('PATH_site', strtr(getenv('TYPO3_PATH_WEB'), '\\', '/') . '/');
    define('PATH_thisScript', realpath(PATH_site . 'typo3/cli_dispatch.phpsh'));

    require __DIR__ . '/../Classes/Core/ConsoleBootstrap.php';
    $bootstrap = new \Helhum\Typo3Console\Core\ConsoleBootstrap(getenv('TYPO3_CONTEXT') ?: 'Production');
    $bootstrap->run($classLoader);
});
