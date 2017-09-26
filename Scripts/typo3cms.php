<?php
(function () {
    if (file_exists($autoLoadFile = dirname(__DIR__) . '/.Build/vendor/autoload.php')) {
        // Console is root package, thus vendor folder is .Build/vendor
        $classLoader = require $autoLoadFile;
    } elseif (file_exists($autoLoadFile = dirname(dirname(dirname(__DIR__))) . '/autoload.php')) {
        // Console is a dependency, thus located in vendor/helhum/typo3-console
        $classLoader = require $autoLoadFile;
    } elseif (file_exists($autoLoadFile = realpath(($rootPath = dirname(dirname(dirname(dirname(__DIR__))))) . '/typo3') . '/../vendor/autoload.php')) {
        // Console is extension, but TYPO3_PATH_ROOT was not set, because binary is symlinked, so set it here
        putenv('TYPO3_PATH_ROOT=' . $rootPath);
        $classLoader = require $autoLoadFile;
    } elseif (file_exists($autoLoadFile = realpath(getenv('TYPO3_PATH_ROOT') . '/typo3') . '/../vendor/autoload.php')) {
        // Console is extension, this TYPO3_PATH_ROOT was set in typo3cms script, which is located in TYPO3 root
        $classLoader = require $autoLoadFile;
    } else {
        if (file_exists(realpath(getenv('TYPO3_PATH_WEB') . '/typo3') . '/../vendor/autoload.php')) {
            // Console is extension, this TYPO3_PATH_WEB was set in outdated typo3cms script, which is located in TYPO3 root
            echo 'You seem to use an outdated typo3cms binary in your TYPO3 root directory.' . PHP_EOL;
            echo 'Please properly install typo3_console in the TYPO3 Extension Manager.' . PHP_EOL;
        }
        echo 'Could not find autoload.php file. Is TYPO3_PATH_ROOT specified correctly?' . PHP_EOL;
        exit(1);
    }

    if (!class_exists(\Helhum\Typo3Console\Core\Kernel::class)) {
        // This require is needed so that the console works in non Composer mode,
        // where requiring the main autoload.php is not enough to load extension classes
        require __DIR__ . '/../Classes/Core/Kernel.php';
    }
    $kernel = new \Helhum\Typo3Console\Core\Kernel($classLoader);
    $kernel->handle();
})();
