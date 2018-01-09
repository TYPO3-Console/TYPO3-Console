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
use Composer\Util\ProcessExecutor;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * This class can be used to execute console commands in a sub process
 * It is especially useful during initial TYPO3 setup, but also when commands
 * need a minimal bootstrap first, but the execute some actions with a fill bootstrap
 * like e.g. the cache:flush command
 */
class CommandDispatcher
{
    /**
     * @var array
     */
    private $commandLine;

    /**
     * @var array
     */
    private $environmentVars;

    /**
     * Don't allow object creation without factory method
     *
     * @param array $commandLine
     * @param array $environmentVars
     */
    private function __construct(array $commandLine, array $environmentVars = [])
    {
        $this->commandLine = $commandLine;
        $this->environmentVars = $environmentVars;
    }

    /**
     * Create the dispatcher from within a composer plugin context
     *
     * Just provide the composer script event to cover most cases
     *
     * @param ScriptEvent $event
     * @param array $commandLine
     * @param array $environmentVars
     * @param PhpExecutableFinder $phpFinder
     * @throws RuntimeException
     * @return CommandDispatcher
     */
    public static function createFromComposerRun(ScriptEvent $event, array $commandLine = [], array $environmentVars = [], PhpExecutableFinder $phpFinder = null)
    {
        // should be Application::COMMAND_NAME, but our Application class currently conflicts with symfony/console 2.7, which is used by Composer
        $name = 'typo3cms';
        $searchDirs = [
            $event->getComposer()->getConfig()->get('bin-dir'),
            dirname(__DIR__, 3),
        ];
        foreach ($searchDirs as $dir) {
            $file = $dir . DIRECTORY_SEPARATOR . $name;
            if (is_file($file)) {
                $typo3CommandPath = $file;
                break;
            }
        }
        if (!isset($typo3CommandPath)) {
            throw new RuntimeException(sprintf('The "%s" binary could not be found.', $name), 1494778973);
        }
        $environmentVars['TYPO3_CONSOLE_PLUGIN_RUN'] = true;

        return self::create($typo3CommandPath, $commandLine, $environmentVars, $phpFinder);
    }

    /**
     * Useful for creating the object during the runtime of another command
     *
     * Just use the method without arguments for best results
     *
     * @param array $commandLine
     * @param array $environmentVars
     * @param PhpExecutableFinder $phpFinder
     * @throws RuntimeException
     * @return CommandDispatcher
     */
    public static function createFromCommandRun(array $commandLine = [], array $environmentVars = [], PhpExecutableFinder $phpFinder = null)
    {
        if (!isset($_SERVER['argv'][0]) && strpos($_SERVER['argv'][0], Application::COMMAND_NAME) === false) {
            throw new RuntimeException('Tried to create typo3 command runner from wrong context', 1484945065);
        }
        $typo3CommandPath = $_SERVER['argv'][0];
        return self::create($typo3CommandPath, $commandLine, $environmentVars, $phpFinder);
    }

    /**
     * Useful for creating the object during the runtime of a test
     *
     * Just use the method without arguments for best results
     *
     * @param string|null $typo3CommandPath
     * @throws RuntimeException
     * @return CommandDispatcher
     */
    public static function createFromTestRun($typo3CommandPath = null)
    {
        if (!isset($_SERVER['argv'][0]) && strpos($_SERVER['argv'][0], 'phpunit') === false) {
            throw new RuntimeException(sprintf('Tried to create %s command runner from wrong context', Application::COMMAND_NAME), 1493570522);
        }
        $typo3CommandPath = $typo3CommandPath ?: dirname(__DIR__, 3) . '/' . Application::COMMAND_NAME;
        return self::create($typo3CommandPath);
    }

    /**
     * Basic factory method, which need the exact path to the typo3 console binary to create the dispatcher
     *
     * @param string $typo3CommandPath Absolute path to the typo3 console binary
     * @param array $commandLine
     * @param array $environmentVars
     * @param PhpExecutableFinder $phpFinder
     * @throws RuntimeException
     * @return CommandDispatcher
     */
    public static function create($typo3CommandPath, array $commandLine = [], array $environmentVars = [], PhpExecutableFinder $phpFinder = null)
    {
        $phpFinder = $phpFinder ?: new PhpExecutableFinder();
        if (!($php = $phpFinder->find())) {
            throw new RuntimeException('The "php" binary could not be found.', 1485128615);
        }
        array_unshift($commandLine, $typo3CommandPath);
        array_unshift($commandLine, $php);
        if (getenv('PHP_INI_PATH')) {
            $commandLine[] = '-c';
            $commandLine[] = getenv('PHP_INI_PATH');
        }
        $environmentVars['TYPO3_CONSOLE_SUB_PROCESS'] = true;
        return new self($commandLine, $environmentVars);
    }

    /**
     * Execute a command in a sub process
     *
     * @param string $command Command identifier
     * @param array $arguments Argument names will automatically be converted to dashed version, if not provided like so
     * @param array $environment Environment vars to be added to the command
     * @param resource|string|\Traversable|null $input Inpupt (stdin) for the command
     * @throws FailedSubProcessCommandException
     * @return string
     */
    public function executeCommand($command, array $arguments = [], array $environment = [], $input = null)
    {
        // Start with a fresh list of arguments and environment
        $commandLine = $this->commandLine;
        $environmentVars = array_replace($this->environmentVars, $environment);
        if (empty($environmentVars)) {
            $environmentVars = null;
        }
        $commandLine[] = $command;

        foreach ($arguments as $argumentName => $argumentValue) {
            if (is_int($argumentName)) {
                $dashedName = $argumentValue;
                $argumentValue = null;
            } elseif (strpos($argumentName, '--') === 0) {
                $dashedName = $argumentName;
            } else {
                $dashedName = ucfirst($argumentName);
                $dashedName = preg_replace('/([A-Z][a-z0-9]+)/', '$1-', $dashedName);
                $dashedName = '--' . strtolower(substr($dashedName, 0, -1));
            }
            if (is_array($argumentValue)) {
                $argumentValue = implode(',', $argumentValue);
            }
            $commandLine[] = $dashedName;
            if ($argumentValue !== null) {
                if ($argumentValue === false) {
                    // Convert boolean false to 'false' instead of empty string to correctly pass the value to the sub command
                    $commandLine[] = 'false';
                } else {
                    $commandLine[] = $argumentValue;
                }
            }
        }

        if (isset($envVars['TYPO3_CONSOLE_PLUGIN_RUN'])) {
            // During a composer run, we have symfony/console 2.8 unfortunately,
            // thus we must handle convert the arguments to a string.
            $process = new Process(
                implode(' ', array_map(ProcessExecutor::class . '::escape', $commandLine)),
                null,
                array_replace($this->getDefaultEnv(), $environmentVars),
                $input,
                0
            );
        } else {
            $process = new Process($commandLine, null, $environmentVars, $input, 0);
            $process->inheritEnvironmentVariables();
        }

        $process->run();
        $output = str_replace("\r\n", "\n", trim($process->getOutput()));

        if (!$process->isSuccessful()) {
            throw FailedSubProcessCommandException::forProcess($command, $process);
        }

        return $output;
    }

    private function getDefaultEnv(): array
    {
        $env = [];

        foreach ($_SERVER as $k => $v) {
            if (is_string($v) && false !== $v = getenv($k)) {
                $env[$k] = $v;
            }
        }

        foreach ($_ENV as $k => $v) {
            if (is_string($v)) {
                $env[$k] = $v;
            }
        }

        return $env;
    }
}
