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
use Helhum\Typo3Console\CompatibilityClassLoader;
use Helhum\Typo3Console\Core\Booting\CompatibilityScripts;
use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Core\Booting\Scripts;
use Helhum\Typo3Console\Exception;
use Helhum\Typo3Console\Mvc\Cli\CommandCollection;
use Helhum\Typo3Console\Mvc\Cli\CommandConfiguration;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Psr\Container\ContainerInterface;
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
     * @var CompatibilityClassLoader
     */
    private $classLoader;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(CompatibilityClassLoader $classLoader)
    {
        $this->ensureRequiredEnvironment();
        $this->classLoader = $classLoader;
        $this->runLevel = new RunLevel();
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
     * Legacy method called by old composer plugins
     *
     * @param ClassLoader $classLoader
     * @internal
     * @deprecated will be removed with 6.0
     */
    public static function initializeCompatibilityLayer(ClassLoader $classLoader)
    {
        new CompatibilityClassLoader($classLoader);
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
            // Initialize basic annotation loader until TYPO3 does so as well
            AnnotationRegistry::registerLoader('class_exists');
            Scripts::baseSetup();
            $this->initialized = true;
        }
        if ($runLevel !== null) {
            $this->runLevel->runSequence($runLevel);
        }

        $container = Bootstrap::init(
            $this->classLoader->getTypo3ClassLoader(),
            true
        );
        // Init symfony DI in TYPO3 v10
        if (class_exists(\TYPO3\CMS\Install\Service\LateBootService::class)) {
            // TODO: this won't work out when SF container config is broken by extensions
            $lateBootService = $container->get(\TYPO3\CMS\Install\Service\LateBootService::class);
            $this->container = $lateBootService->getContainer();
            $lateBootService->makeCurrent($this->container);
        } else {
            $this->container = $container;
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
        if ($exitCode > 255) {
            $exitCode = 255;
        }
        exit($exitCode);
    }
}
