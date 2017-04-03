<?php
call_user_func(function () {
    $log = function ($message, $debug = false) {
        if (getenv('TYPO3_CONSOLE_SUB_PROCESS') || ($debug && !getenv('TYPO3_CONSOLE_DEBUG'))) {
            return;
        }
        echo $message . PHP_EOL;
    };

    // Exit early if php requirement is not satisfied.
    if (version_compare(PHP_VERSION, '5.5.0', '<')) {
        $log('This version of TYPO3 Console requires PHP 5.5.0 or above!');
        if (defined('PHP_BINARY')) {
            $log('Your PHP binary is located at: "' . PHP_BINARY . '",');
            $log('but its version is only: ' . PHP_VERSION);
        } else {
            $log('Your PHP version is: ' . PHP_VERSION);
        }
        $log('Please specify a suitable PHP cli binary before the typo3cms binary like that:');
        $log('/path/to/php55-latest ' . $_SERVER['argv'][0]);
        exit(1);
    }

    if (getenv('TYPO3_PATH_WEB')) {
        // In case we are symlinked (like for travis tests),
        // we need to accept the location from the outside to find the autoload.php
        $typo3Root = getenv('TYPO3_PATH_WEB');
        $log('Root path: ' . $typo3Root, true);
    } else {
        // Assume TYPO3 web root and vendor dir (only one is applicable at the same time)
        // Both only works if the package is *NOT* symlinked to the typo3conf/ext or vendor folder
        $typo3Root = dirname(dirname(dirname(dirname(__DIR__))));
        $vendorDir = dirname(dirname(dirname(__DIR__)));
    }
    if (file_exists($autoLoadFile = realpath($typo3Root . '/typo3') . '/../vendor/autoload.php')) {
        // The extension is in typo3conf/ext, so we load the main autoload.php from TYPO3 sources
        // Applicable in both Composer and non-Composer mode.
        $classLoader = require $autoLoadFile;
    } elseif (!empty($vendorDir) && file_exists($autoLoadFile = $vendorDir . '/autoload.php')) {
        // The package is in vendor dir, so we load the main autoload.php from vendor folder
        // Applicable in Composer mode only.
        $classLoader = require $autoLoadFile;
    } else {
        echo 'Could not find autoload.php file. Is TYPO3_PATH_WEB specified correctly?' . PHP_EOL;
        exit(1);
    }

    if (!getenv('TYPO3_PATH_WEB')) {
        // Fallback to binary location in document root, if the plugin was not available (non Composer mode)
        // Applicable in both Composer mode (when TYPO3_PATH_WEB was specified) and non-Composer mode.
        putenv('TYPO3_PATH_WEB=' . $typo3Root);
    }

    define('PATH_site', str_replace('\\', '/', realpath(getenv('TYPO3_PATH_WEB'))) . '/');
    define('PATH_thisScript', PATH_site . 'typo3/cli_dispatch.phpsh');

    $log('PATH_site: ' . PATH_site, true);
    $log('PATH_thisScript: ' . PATH_thisScript, true);
    $log('is_file(PATH_thisScript): ' . var_export(is_file(PATH_thisScript), true), true);

    if (!class_exists('Helhum\\Typo3Console\\Core\\ConsoleBootstrap')) {
        // This require is needed so that the console works in non Composer mode,
        // where requiring the main autoload.php is not enough to load extension classes
        require __DIR__ . '/../Classes/Core/ConsoleBootstrap.php';
    }
    $bootstrap = \Helhum\Typo3Console\Core\ConsoleBootstrap::create(getenv('TYPO3_CONTEXT') ?: 'Production');
    $bootstrap->run($classLoader);
});
