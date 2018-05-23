<?php
declare(strict_types=1);
(function () {
    if (file_exists($rootAutoLoadFile = dirname(__DIR__) . '/.Build/vendor/autoload.php')) {
        // Console is root package, thus vendor folder is .Build/vendor
        $classLoader = require $rootAutoLoadFile;
    } elseif (file_exists($vendorAutoLoadFile = dirname(dirname(dirname(__DIR__))) . '/autoload.php')) {
        // Console is a dependency, thus located in vendor/helhum/typo3-console
        $classLoader = require $vendorAutoLoadFile;
    } elseif (file_exists($typo3AutoLoadFile = __DIR__ . '/../ext_autoload.php')) {
        // Console is extension
        $classLoader = require $typo3AutoLoadFile;
    } else {
        echo 'Could not find autoload.php file. TYPO3 Console needs to be installed with composer' . PHP_EOL;
        exit(1);
    }

    $kernel = new \Helhum\Typo3Console\Core\Kernel($classLoader);
    $exitCode = $kernel->handle(new \Helhum\Typo3Console\Mvc\Cli\Symfony\Input\ArgvInput());
    $kernel->terminate($exitCode);
})();
