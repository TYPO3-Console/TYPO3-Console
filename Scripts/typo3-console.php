<?php
declare(strict_types=1);
(static function () {
    if ($vendorAutoLoadFile = $GLOBALS['_composer_autoload_path'] ?? null) {
        $classLoader = require $vendorAutoLoadFile;
    } elseif (file_exists($vendorAutoLoadFile = dirname(__DIR__) . '/.Build/vendor/autoload.php')) {
        // Console is root package, thus vendor folder is .Build/vendor
        $classLoader = require $vendorAutoLoadFile;
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
