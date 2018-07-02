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
    require __DIR__ . '/Libraries/vendor/autoload.php';

    return $classLoader;
})();
