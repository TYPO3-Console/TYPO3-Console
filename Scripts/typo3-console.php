<?php
declare(strict_types=1);
(function () {
    // Exit early if php requirement is not satisfied.
    if (version_compare(PHP_VERSION, '7.0.0', '<')) {
        echo 'This version of TYPO3 Console requires PHP 7.0.0 or above!' . PHP_EOL;
        if (defined('PHP_BINARY')) {
            echo 'Your PHP binary is located at: "' . PHP_BINARY . '",' . PHP_EOL;
            echo 'but its version is only: ' . PHP_VERSION . PHP_EOL;
        } else {
            echo 'Your PHP version is: ' . PHP_VERSION . PHP_EOL;
        }
        echo 'Please specify a suitable PHP cli binary before the typo3cms binary like that:' . PHP_EOL;
        echo '/path/to/php70-latest ' . $_SERVER['argv'][0] . PHP_EOL;
        exit(1);
    }

    if (file_exists($rootAutoLoadFile = dirname(__DIR__) . '/.Build/vendor/autoload.php')) {
        // Console is root package, thus vendor folder is .Build/vendor
        $classLoader = require $rootAutoLoadFile;
    } elseif (file_exists($vendorAutoLoadFile = dirname(dirname(dirname(__DIR__))) . '/autoload.php')) {
        // Console is a dependency, thus located in vendor/helhum/typo3-console
        $classLoader = require $vendorAutoLoadFile;
    } else {
        echo 'Could not find autoload.php file. TYPO3 Console needs to be installed with composer' . PHP_EOL;
        exit(1);
    }

    $kernel = new \Helhum\Typo3Console\Core\Kernel(new \Helhum\Typo3Console\CompatibilityClassLoader($classLoader));
    $exitCode = $kernel->handle(new \Helhum\Typo3Console\Mvc\Cli\Symfony\Input\ArgvInput());
    $kernel->terminate($exitCode);
})();
