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

use Composer\Script\Event as ScriptEvent;
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
     * Just provide the composer script event to cover most cases
     *
     * @param ScriptEvent $event
     * @param ProcessBuilder $processBuilder
     * @param PhpExecutableFinder $phpFinder
     * @return CommandDispatcher
     */
    public static function createFromComposerRun(ScriptEvent $event, ProcessBuilder $processBuilder = null, PhpExecutableFinder $phpFinder = null)
    {
        $name = 'typo3cms';
        $searchDirs = [
            $event->getComposer()->getConfig()->get('bin-dir'),
            dirname(dirname(dirname(__DIR__))) . '/Scripts',
        ];
        foreach ($searchDirs as $dir) {
            $file = $dir . DIRECTORY_SEPARATOR . $name;
            if (is_file($file)) {
                $typo3cmsCommandPath = $file;
                break;
            }
        }
        if (!isset($typo3cmsCommandPath)) {
            throw new \RuntimeException('The "typo3cms" binary could not be found.', 1494778973);
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
     * Useful for creating the object during the runtime of a test
     *
     * Just use the method without arguments for best results
     *
     * @param string|null $typo3cmsCommandPath
     * @return CommandDispatcher
     */
    public static function createFromTestRun($typo3cmsCommandPath = null)
    {
        if (!isset($_SERVER['argv'][0]) && strpos($_SERVER['argv'][0], 'phpunit') === false) {
            throw new \RuntimeException('Tried to create typo3cms command runner from wrong context', 1493570522);
        }
        $typo3cmsCommandPath = $typo3cmsCommandPath ?: dirname(dirname(dirname(__DIR__))) . '/Scripts/typo3cms';
        return self::create($typo3cmsCommandPath);
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
     * @param resource|string|\Traversable|null $input Inpupt (stdin) for the command
     * @throws FailedSubProcessCommandException
     * @return string
     */
    public function executeCommand($command, array $arguments = [], array $environment = [], $input = null)
    {
        // We need to clone the builder to be able to re-use the object for multiple commands
        $processBuilder = clone $this->processBuilder;

        $processBuilder->add($command);
        $processBuilder->addEnvironmentVariables($environment);
        $processBuilder->setInput($input);

        foreach ($arguments as $argumentName => $argumentValue) {
            if (strpos($argumentName, '--') === 0) {
                $dashedName = $argumentName;
            } else {
                $dashedName = ucfirst($argumentName);
                $dashedName = preg_replace('/([A-Z][a-z0-9]+)/', '$1-', $dashedName);
                $dashedName = '--' . strtolower(substr($dashedName, 0, -1));
            }
            if (is_array($argumentValue)) {
                $argumentValue = implode(',', $argumentValue);
            }
            if (strpos($argumentValue, '=') !== false) {
                // Big WTF in argument parsing here
                // If the value contains a = we need to separate the name and value with a = ourselves
                // to get the parser to correctly read our value
                $processBuilder->add($dashedName . '=' . $argumentValue);
            } else {
                $processBuilder->add($dashedName);
                if ($argumentValue !== null) {
                    if ($argumentValue === false) {
                        // Convert boolean false to 'false' instead of empty string to correctly pass the value to the sub command
                        $processBuilder->add('false');
                    } else {
                        $processBuilder->add($argumentValue);
                    }
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
