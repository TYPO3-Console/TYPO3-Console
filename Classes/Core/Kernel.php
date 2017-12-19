<?php
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
use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Core\Booting\Scripts;
use Helhum\Typo3Console\Mvc\Cli\CommandCollection;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Symfony\Component\Console\Input\InputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\CommandManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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

    public function __construct(\Composer\Autoload\ClassLoader $classLoader)
    {
        $this->classLoader = $classLoader;
        $this->ensureRequiredEnvironment();
        $this->bootstrap = Bootstrap::getInstance();
        $this->bootstrap->initializeClassLoader($classLoader);
        $this->runLevel = new RunLevel($this->bootstrap);
    }

    /**
     * Checks PHP sapi type and sets required PHP options
     */
    private function ensureRequiredEnvironment()
    {
        if (PHP_SAPI !== 'cli' || !isset($_SERVER['argc'], $_SERVER['argv'])) {
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
     * Register auto loading for our own classes in case we cannot rely on composer class loading.
     */
    private function initializeNonComposerClassLoading()
    {
        if (Bootstrap::usesComposerClassLoading()) {
            return;
        }
        $classesPaths = [__DIR__ . '/../../Classes', __DIR__ . '/../../Resources/Private/ExtensionArtifacts/src/'];
        $classLoader = new ClassLoader();
        $classLoader->addPsr4('Helhum\\Typo3Console\\', $classesPaths);
        spl_autoload_register(function ($className) use ($classLoader) {
            if ($file = $classLoader->findFile($className)) {
                require $file;
            }
        });
        $pharFile = __DIR__ . '/../../Libraries/symfony-process.phar';
        require 'phar://' . $pharFile . '/vendor/autoload.php';
    }

    /**
     * If detected TYPO3 version does not match the main supported version,
     * overlay compatibility classes for the detected branch, by registering
     * an autoloader and aliasing the compatibility class with the original class name.
     */
    private function initializeCompatibilityLayer()
    {
        $typo3Branch = '90';
        if (method_exists($this->bootstrap, 'setCacheHashOptions')) {
            $typo3Branch = '87';
        }
        if ($typo3Branch === '90') {
            return;
        }
        $compatibilityNamespace = 'Helhum\\Typo3Console\\TYPO3v' . $typo3Branch . '\\';
        spl_autoload_register(function ($className) use ($compatibilityNamespace) {
            if (strpos($className, 'Helhum\\Typo3Console\\') !== 0) {
                // We don't care about classes that are not within our namespace
                return;
            }
            $compatibilityClassName = str_replace('Helhum\\Typo3Console\\', $compatibilityNamespace, $className);
            if ($file = $this->classLoader->findFile($compatibilityClassName)) {
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
     * @throws \Helhum\Typo3Console\Exception
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public function initialize(string $runLevel = null)
    {
        if (!$this->initialized) {
            $this->initializeNonComposerClassLoading();
            $this->initializeCompatibilityLayer();
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
     * @throws \Helhum\Typo3Console\Exception
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @return int
     */
    public function handle(InputInterface $input): int
    {
        $this->initialize();

        $commandRegistry = new CommandCollection(
            $this->runLevel,
            GeneralUtility::makeInstance(PackageManager::class)
        );

        $application = new Application($this->runLevel, Bootstrap::usesComposerClassLoading());
        $application->addCommandsIfAvailable($commandRegistry);

        $commandIdentifier = $input->getFirstArgument() ?: '';
        $this->runLevel->runSequenceForCommand($commandIdentifier);
        $application->addCommandsIfAvailable($commandRegistry->addCommandControllerCommands(GeneralUtility::makeInstance(ObjectManager::class)->get(CommandManager::class)));

        return $application->run($input);
    }

    /**
     * Finish the current request and exit with the given exit code
     *
     * @param int $exitCode
     */
    public function terminate(int $exitCode = 0)
    {
        $this->bootstrap->shutdown();
        if ($exitCode > 255) {
            $exitCode = 255;
        }
        exit($exitCode);
    }
}
