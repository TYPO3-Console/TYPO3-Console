<?php
namespace Helhum\Typo3Console\Mvc\Cli;

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

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;

/**
 * This class can be used to execute console commands in a sub process
 * It is especially useful during initial TYPO3 setup, but also when commands
 * need a minimal bootstrap first, but the execute some actions with a fill bootstrap
 * like e.g. the cache:flush command
 */
class CommandDispatcher
{
    /**
     * @var ProcessBuilder
     */
    private $processBuilder;

    /**
     * Don't allow object creation without factory method
     *
     * @param ProcessBuilder $processBuilder
     */
    private function __construct(ProcessBuilder $processBuilder)
    {
        $this->processBuilder = $processBuilder;
    }

    /**
     * Create the dispatcher from within a composer plugin context
     *
     * Provide the composer bin-dir as search dir to cover most cases
     *
     * @param array $searchDirs Directories to look for the typo3cms binary.
     * @param ExecutableFinder $binFinder
     * @param ProcessBuilder $processBuilder
     * @param PhpExecutableFinder $phpFinder
     * @throws \RuntimeException
     * @return self
     */
    public static function createFromComposerRun(array $searchDirs, ExecutableFinder $binFinder = null, ProcessBuilder $processBuilder = null, PhpExecutableFinder $phpFinder = null)
    {
        $binFinder = $binFinder ?: new ExecutableFinder();
        $name = 'typo3cms';
        $suffixes = [''];
        if ('\\' === DIRECTORY_SEPARATOR) {
            $suffixes[] = '.bat';
        }
        // The finder first looks in the system directories and then in the
        // user-defined ones. We want to check the user-defined ones first.
        foreach ($searchDirs as $dir) {
            foreach ($suffixes as $suffix) {
                $file = $dir . DIRECTORY_SEPARATOR . $name . $suffix;
                if (is_file($file) && ('\\' === DIRECTORY_SEPARATOR || is_executable($file))) {
                    $typo3cmsCommandPath = $file;
                    break 2;
                }
            }
        }
        if (!isset($typo3cmsCommandPath)) {
            $typo3cmsCommandPath = $binFinder->find($name);
        }
        $processBuilder = $processBuilder ?: new ProcessBuilder();
        $processBuilder->addEnvironmentVariables(['TYPO3_CONSOLE_PLUGIN_RUN' => true]);

        return self::create($typo3cmsCommandPath, $processBuilder, $phpFinder);
    }

    /**
     * Useful for creating the object during the runtime of another command
     *
     * Just use the method without arguments for best results
     *
     * @param ProcessBuilder $processBuilder
     * @param PhpExecutableFinder $phpFinder
     * @throws \RuntimeException
     * @return self
     */
    public static function createFromCommandRun(ProcessBuilder $processBuilder = null, PhpExecutableFinder $phpFinder = null)
    {
        if (!isset($_SERVER['argv'][0]) && strpos($_SERVER['argv'][0], 'typo3cms') === false) {
            throw new \RuntimeException('Tried to create typo3cms command runner from wrong context', 1484945065);
        }
        $typo3cmsCommandPath = $_SERVER['argv'][0];
        return self::create($typo3cmsCommandPath, $processBuilder, $phpFinder);
    }

    /**
     * Basic factory method, which need the exact path to the typo3cms binary to create the dispatcher
     *
     * @param string $typo3cmsCommandPath Absolute path to the typo3cms binary
     * @param ProcessBuilder $processBuilder
     * @param PhpExecutableFinder $phpFinder
     * @throws \RuntimeException
     * @return self
     */
    public static function create($typo3cmsCommandPath, ProcessBuilder $processBuilder = null, PhpExecutableFinder $phpFinder = null)
    {
        $processBuilder = $processBuilder ?: new ProcessBuilder();
        $phpFinder = $phpFinder ?: new PhpExecutableFinder();
        if (!($php = $phpFinder->find())) {
            throw new \RuntimeException('The "php" binary could not be found.', 1485128615);
        }
        $processBuilder->setPrefix($php);
        $processBuilder->add($typo3cmsCommandPath);
        $processBuilder->addEnvironmentVariables(['TYPO3_CONSOLE_SUB_PROCESS' => true]);
        return new self($processBuilder);
    }

    /**
     * Execute a command in a sub process
     *
     * @param string $command Command identifier
     * @param array $arguments Argument names will automatically be converted to dashed version, fi not provided like so
     * @param array $environment Environment vars to be added to the command
     * @throws FailedSubProcessCommandException
     * @return string Output of the executed command
     */
    public function executeCommand($command, array $arguments = [], array $environment = [])
    {
        // We need to clone the builder to be able to re-use the object for multiple commands
        $processBuilder = clone $this->processBuilder;

        $processBuilder->add($command);
        $processBuilder->addEnvironmentVariables($environment);

        foreach ($arguments as $argumentName => $argumentValue) {
            if (strpos($argumentName, '--') === 0) {
                $dashedName = $argumentName;
            } else {
                $dashedName = ucfirst($argumentName);
                $dashedName = preg_replace('/([A-Z][a-z0-9]+)/', '$1-', $dashedName);
                $dashedName = '--' . strtolower(substr($dashedName, 0, -1));
            }
            if ($argumentValue !== null) {
                if ($argumentValue === false) {
                    // Convert boolean false to 'false' instead of empty string to correctly pass the value to the sub command
                    $processBuilder->add($dashedName);
                    $processBuilder->add('false');
                } else {
                    $processBuilder->add($dashedName . '=' . $argumentValue);
                }
            }
        }

        $process = $processBuilder->setTimeout(null)->getProcess();
        $process->run();
        $output = str_replace("\r\n", "\n", trim($process->getOutput()));

        if (!$process->isSuccessful()) {
            throw FailedSubProcessCommandException::forProcess($command, $process);
        }

        return $output;
    }
}
