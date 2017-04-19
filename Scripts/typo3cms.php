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

    if (file_exists($autoLoadFile = dirname(dirname(dirname(__DIR__))) . '/autoload.php')) {
        // Console is a dependency, thus located in vendor/helhum/typo3-console
        $classLoader = require $autoLoadFile;
    } elseif (file_exists($autoLoadFile = realpath(($rootPath = dirname(dirname(dirname(dirname(__DIR__))))) . '/typo3') . '/../vendor/autoload.php')) {
        // Console is extension, but TYPO3_PATH_ROOT was not set, because binary is symlinked, so set it here
        putenv('TYPO3_PATH_ROOT=' . $rootPath);
        $classLoader = require $autoLoadFile;
    } elseif (file_exists($autoLoadFile = realpath(getenv('TYPO3_PATH_ROOT') . '/typo3') . '/../vendor/autoload.php')) {
        // Console is extension, this TYPO3_PATH_ROOT was set in typo3cms script, which is located in TYPO3 root
        $classLoader = require $autoLoadFile;
    } elseif (file_exists($autoLoadFile = dirname(__DIR__) . '/.Build/vendor/autoload.php')) {
        // Console is root package, thus vendor folder is .Build/vendor
        $classLoader = require $autoLoadFile;
    } else {
        if (file_exists(realpath(getenv('TYPO3_PATH_WEB') . '/typo3') . '/../vendor/autoload.php')) {
            // Console is extension, this TYPO3_PATH_WEB was set in outdated typo3cms script, which is located in TYPO3 root
            $log('You seem to use an outdated typo3cms binary in your TYPO3 root directory.');
            $log('Please properly install typo3_console in the TYPO3 Extension Manager.');
        }
        $log('Could not find autoload.php file. Is TYPO3_PATH_ROOT specified correctly?');
        exit(1);
    }

    define('PATH_site', \TYPO3\CMS\Core\Utility\GeneralUtility::fixWindowsFilePath(getenv('TYPO3_PATH_ROOT')) . '/');
    if (file_exists(PATH_site . 'typo3/sysext/core/bin/typo3')) {
        define('PATH_thisScript', PATH_site . 'typo3/sysext/core/bin/typo3');
    } else {
        // @deprecated will be removed once 7.6 support is removed
        define('PATH_thisScript', PATH_site . 'typo3/cli_dispatch.phpsh');
    }

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
