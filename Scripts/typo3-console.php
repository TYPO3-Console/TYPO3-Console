<?php
(function () {
    if (file_exists($rootAutoLoadFile = dirname(__DIR__) . '/.Build/vendor/autoload.php')) {
        // Console is root package, thus vendor folder is .Build/vendor
        $classLoader = require $rootAutoLoadFile;
    } elseif (file_exists($vendorAutoLoadFile = dirname(dirname(dirname(__DIR__))) . '/autoload.php')) {
        // Console is a dependency, thus located in vendor/helhum/typo3-console
        $classLoader = require $vendorAutoLoadFile;
    } elseif (file_exists($typo3AutoLoadFile = realpath(($rootPath = dirname(dirname(dirname(dirname(__DIR__))))) . '/typo3') . '/../vendor/autoload.php')) {
        // Console is extension
        putenv('TYPO3_PATH_ROOT=' . $rootPath);
        $classLoader = require $typo3AutoLoadFile;
    } else {
        echo 'Could not find autoload.php file. Is TYPO3_PATH_ROOT specified correctly?' . PHP_EOL;
        exit(1);
    }

    if (!class_exists(\Helhum\Typo3Console\Core\Kernel::class)) {
        // This require is needed so that the console works in non Composer mode,
        // where requiring the main autoload.php is not enough to load extension classes
        require __DIR__ . '/../Classes/Core/Kernel.php';
    }
    $kernel = new \Helhum\Typo3Console\Core\Kernel($classLoader);
    $exitCode = $kernel->handle(new \Helhum\Typo3Console\Mvc\Cli\Symfony\Input\ArgvInput());
    $kernel->terminate($exitCode);
})();
