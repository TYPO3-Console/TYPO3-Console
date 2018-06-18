<?php
declare(strict_types=1);

return (function () {
    static $classLoader;
    if ($classLoader) {
        return $classLoader;
    }

    $typo3AutoLoadFile = realpath(($rootPath = dirname(__DIR__, 3)) . '/typo3') . '/../vendor/autoload.php';
    putenv('TYPO3_PATH_ROOT=' . $rootPath);
    $classLoader = require $typo3AutoLoadFile;

    $extensionBaseDir = __DIR__;
    $autoloadDefinition = json_decode(file_get_contents($extensionBaseDir . '/composer.json'), true)['autoload']['psr-4'];
    foreach ($autoloadDefinition as $prefix => $paths) {
        $paths = array_map(
            function ($path) use ($extensionBaseDir) {
                return $extensionBaseDir . '/' . $path;
            },
            (array)$paths
        );
        $classLoader->addPsr4($prefix, $paths);
    }
    $pharFile = __DIR__ . '/Libraries/symfony-process.phar';
    require 'phar://' . $pharFile . '/vendor/autoload.php';

    return $classLoader;
})();
