<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Core;

/*
 * This file is part of the TYPO3 Console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Helhum\Typo3Console\Core\Booting\CompatibilityScripts;
use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Core\Booting\Scripts;
use Helhum\Typo3Console\Exception;
use Helhum\Typo3Console\Mvc\Cli\CommandCollection;
use Helhum\Typo3Console\Mvc\Cli\CommandConfiguration;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
class Kernel
{
    /**
     * @var ClassLoader
     */
    private $classLoader;

    /**
     * @var Bootstrap
     */
    private $bootstrap;

    /**
     * @var RunLevel
     */
    private $runLevel;

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var ClassLoader
     */
    public static $nonComposerCompatClassLoader;

    public function __construct(\Composer\Autoload\ClassLoader $classLoader)
    {
        $this->classLoader = $classLoader;
        $this->ensureRequiredEnvironment();
        $this->bootstrap = Bootstrap::getInstance();
        $this->bootstrap->initializeClassLoader($classLoader);
        // Initialize basic annotation loader until TYPO3 does so as well
        AnnotationRegistry::registerLoader('class_exists');
        $this->runLevel = new RunLevel($this->bootstrap);
    }

    /**
     * Checks PHP sapi type and sets required PHP options
     */
    private function ensureRequiredEnvironment()
    {
        if (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true) || !isset($_SERVER['argc'], $_SERVER['argv'])) {
            echo 'The command line must be executed with a cli PHP binary! The current PHP sapi type is "' . PHP_SAPI . '".' . PHP_EOL;
            exit(1);
        }
        if (ini_get('memory_limit') !== '-1') {
            @ini_set('memory_limit', '-1');
        }
        if (ini_get('max_execution_time') !== '0') {
            @ini_set('max_execution_time', '0');
        }
    }

    /**
     * If detected TYPO3 version does not match the main supported version,
     * overlay compatibility classes for the detected branch, by registering
     * an autoloader and aliasing the compatibility class with the original class name.
     *
     * @param ClassLoader $classLoader
     * @internal
     */
    public static function initializeCompatibilityLayer(ClassLoader $classLoader)
    {
        $typo3Branch = '95';
        if (method_exists(Bootstrap::class, 'setCacheHashOptions')) {
            $typo3Branch = '87';
        }
        if ($typo3Branch === '95') {
            return;
        }
        $classLoader = self::$nonComposerCompatClassLoader ?? $classLoader;
        $compatibilityNamespace = 'Helhum\\Typo3Console\\TYPO3v' . $typo3Branch . '\\';
        spl_autoload_register(function ($className) use ($classLoader, $compatibilityNamespace) {
            if (strpos($className, 'Helhum\\Typo3Console\\') !== 0) {
                // We don't care about classes that are not within our namespace
                return;
            }
            $compatibilityClassName = str_replace('Helhum\\Typo3Console\\', $compatibilityNamespace, $className);
            if ($file = $classLoader->findFile($compatibilityClassName)) {
                require $file;
                class_alias($compatibilityClassName, $className);
            }
        }, true, true);
    }

    /**
     * This is useful to bootstrap the console application
     * without actually executing a command (e.g. during composer install)
     *
     * @param string $runLevel
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function initialize(string $runLevel = null)
    {
        if (!$this->initialized) {
            self::initializeCompatibilityLayer($this->classLoader);
            Scripts::baseSetup($this->bootstrap);
            $this->initialized = true;
        }
        if ($runLevel !== null) {
            $this->runLevel->runSequence($runLevel);
        }
    }

    /**
     * Handle the given command input and return the exit code of the called command
     *
     * @param InputInterface $input
     * @throws Exception
     * @throws InvalidArgumentException
     * @return int
     */
    public function handle(InputInterface $input): int
    {
        $this->initialize();

        $commandCollection = new CommandCollection(
            $this->runLevel,
            new CommandConfiguration(GeneralUtility::makeInstance(PackageManager::class))
        );

        $application = new Application($this->runLevel, CompatibilityScripts::isComposerMode());
        $application->setCommandLoader($commandCollection);

        // Try to resolve short command names and aliases
        $commandName = $commandCollection->find($input->getFirstArgument() ?: 'list');
        if ($this->runLevel->isCommandAvailable($commandName)) {
            $this->runLevel->runSequenceForCommand($commandName);
            $commandCollection->addCommandControllerCommands($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] ?? []);
        }

        return $application->run($input);
    }

    /**
     * Finish the current request and exit with the given exit code
     *
     * @param int $exitCode
     */
    public function terminate(int $exitCode = 0)
    {
        if ($exitCode > 255 || ($exitCode === 0 && $this->runLevel->getError())) {
            $exitCode = 255;
        }
        exit($exitCode);
    }
}
