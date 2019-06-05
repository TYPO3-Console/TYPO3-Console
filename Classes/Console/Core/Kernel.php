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

    public function __construct(CompatibilityClassLoader $classLoader)
    {
        $this->ensureRequiredEnvironment();
        $this->bootstrap = Bootstrap::getInstance();
        $this->bootstrap->initializeClassLoader($classLoader->getTypo3ClassLoader());
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
        $givenCommandName = $input->getFirstArgument() ?: 'list';
        $commandNameCandidate = $commandCollection->find($givenCommandName);
        if ($this->runLevel->isCommandAvailable($commandNameCandidate)) {
            $this->runLevel->runSequenceForCommand($commandNameCandidate);
            // @deprecated will be removed once command controller support is removed
            if ($this->runLevel->getRunLevelForCommand($commandNameCandidate) !== RunLevel::LEVEL_ESSENTIAL) {
                $commandCollection->addCommandControllerCommands($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] ?? []);
                $commandName = $commandCollection->find($givenCommandName);
                if ($commandNameCandidate !== $commandName) {
                    // Mitigate #779 and #778 at least when command controller commands conflict with non low level
                    // previously registered commands
                    $this->runLevel->runSequenceForCommand($commandName);
                }
            }
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
