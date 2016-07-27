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
        // Assume TYPO3 web root and vendor dir (only one is applicable at the same time)
        // Both only works if the package is *NOT* symlinked to the typo3conf/ext or vendor folder
        $typo3Root = dirname(dirname(dirname(dirname(__DIR__))));
        $vendorDir = dirname(dirname(dirname(__DIR__)));
    }
    if (file_exists($autoLoadFile = realpath($typo3Root . '/typo3') . '/../vendor/autoload.php')) {
        // The extension is in typo3conf/ext, so we load the main autoload.php from TYPO3 sources
        // Applicable in both composer and non-composer mode.
        $classLoader = require_once $autoLoadFile;
    } elseif (!empty($vendorDir) && file_exists($autoLoadFile = $vendorDir . '/autoload.php')) {
        // The package is in vendor dir, so we load the main autoload.php from vendor folder
        // Applicable in composer mode only.
        $classLoader = require_once $autoLoadFile;
    } else {
        echo 'Could not find autoload.php file. Is TYPO3_PATH_WEB specified correctly?' . PHP_EOL;
        exit(1);
    }

    if (!getenv('TYPO3_PATH_WEB')) {
        // Fallback to binary location in document root, if the plugin was not available (non composer mode)
        // Applicable in both composer mode (when TYPO3_PATH_WEB was specified) and non-composer mode.
        putenv('TYPO3_PATH_WEB=' . $typo3Root);
    }

    define('PATH_site', str_replace('\\', '/', rtrim(getenv('TYPO3_PATH_WEB'), '\\/')) . '/');
    define('PATH_thisScript', realpath(PATH_site . 'typo3/cli_dispatch.phpsh'));

    if (!class_exists('Helhum\\Typo3Console\\Core\\ConsoleBootstrap')) {
        // This require is needed so that the console works in non composer mode,
        // where requiring the main autoload.php is not enough to load extension classes
        require __DIR__ . '/../Classes/Core/ConsoleBootstrap.php';
    }
    $bootstrap = new \Helhum\Typo3Console\Core\ConsoleBootstrap(getenv('TYPO3_CONTEXT') ?: 'Production');
    $bootstrap->run($classLoader);
});
