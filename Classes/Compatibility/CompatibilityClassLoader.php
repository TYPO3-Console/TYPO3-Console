<?php
declare(strict_types=1);
namespace Helhum\Typo3Console;

use Composer\Autoload\ClassLoader;
use TYPO3\CMS\Core\Core\Bootstrap;

/**
 * If detected TYPO3 version does not match the main supported version,
 * overlay compatibility classes for the detected branch, by registering
 * an autoloader and aliasing the compatibility class with the original class name.
 *
 * Also loads TYPO3 classes in non composer mode.
 *
 * @internal
 */
class CompatibilityClassLoader
{
    /**
     * @var ClassLoader
     */
    private $originalClassLoader;

    /**
     * @var ClassLoader
     */
    private $typo3ClassLoader;

    /**
     * @var string
     */
    private $compatibilityNamespace;

    public function __construct(ClassLoader $originalClassLoader)
    {
        $this->originalClassLoader = $this->typo3ClassLoader = $originalClassLoader;
        $this->handleExtensionCompatibility($originalClassLoader);
        $this->handleTypo3Compatibility();
    }

    public function loadClass($class): bool
    {
        if (strpos($class, 'Helhum\\Typo3Console\\') !== 0) {
            // We don't care about classes that are not within our namespace
            return false;
        }
        $compatibilityClassName = str_replace('Helhum\\Typo3Console\\', $this->compatibilityNamespace, $class);
        if ($file = $this->originalClassLoader->findFile($compatibilityClassName)) {
            \Composer\Autoload\includeFile($file);
            class_alias($compatibilityClassName, $class);

            return true;
        }

        return false;
    }

    public function getTypo3ClassLoader(): ClassLoader
    {
        return $this->typo3ClassLoader;
    }

    private function handleExtensionCompatibility(ClassLoader $originalClassLoader)
    {
        if (class_exists(Bootstrap::class)) {
            return;
        }
        $typo3AutoLoadFile = realpath(($rootPath = dirname(__DIR__, 8)) . '/typo3') . '/../vendor/autoload.php';
        putenv('TYPO3_PATH_ROOT=' . $rootPath);
        $_ENV['TYPO3_PATH_ROOT'] = $rootPath;
        $_SERVER['TYPO3_PATH_ROOT'] = $rootPath;
        $originalClassLoader->unregister();
        $this->typo3ClassLoader = require $typo3AutoLoadFile;
        $originalClassLoader->register(true);
    }

    private function handleTypo3Compatibility()
    {
        if (!method_exists(Bootstrap::class, 'setCacheHashOptions')) {
            return;
        }
        $this->compatibilityNamespace = 'Helhum\\Typo3Console\\TYPO3v87\\';
        spl_autoload_register([$this, 'loadClass'], true, true);
    }
}
