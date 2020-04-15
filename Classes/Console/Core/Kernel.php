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
use Helhum\Typo3Console\CompatibilityClassLoader;
use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Core\Booting\Step;
use Helhum\Typo3Console\Core\Booting\StepFailedException;
use Helhum\Typo3Console\Exception;
use Helhum\Typo3Console\Mvc\Cli\CommandCollection;
use Helhum\Typo3Console\Mvc\Cli\CommandConfiguration;
use Helhum\Typo3Console\Mvc\Cli\CommandLoaderCollection;
use Helhum\Typo3Console\Mvc\Cli\FilteredCommandLoaderCollection;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * @internal
 */
class Kernel
{
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
            SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_CLI);
            $container = Bootstrap::init(
                $this->classLoader->getTypo3ClassLoader(),
                true
            );
            // @TODO: Can be removed, once TYPO3 does not start buffering on CLI within Bootstrap::init()
            ob_end_flush();
            $error = null;
            try {
                $lateBootService = $container->get(\TYPO3\CMS\Install\Service\LateBootService::class);
                $this->container = $lateBootService->getContainer();
                $lateBootService->makeCurrent($this->container);
                ExtensionManagementUtility::setEventDispatcher($this->container->get(EventDispatcherInterface::class));
            } catch (\Throwable $e) {
                $this->container = $container;
                $error = new StepFailedException(new Step('build-container', function () {
                }), $e);
            }
            $this->runLevel = new RunLevel($this->container, $error);
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

        $commandConfiguration = new CommandConfiguration();
        $consoleCommandCollection = new CommandCollection($commandConfiguration);
        $consoleCommandCollection->initializeRunLevel($this->runLevel);

        $commandCollection = new CommandLoaderCollection(
            $consoleCommandCollection,
            new FilteredCommandLoaderCollection(
                $this->container->get(CommandRegistry::class),
                $commandConfiguration->getReplaces()
            )
        );

        $application = new Application($this->runLevel, Environment::isComposerMode());
        $application->setCommandLoader($commandCollection);

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
