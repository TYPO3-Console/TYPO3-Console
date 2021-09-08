<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Database\Process;

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

use Helhum\Typo3Console\Mvc\Cli\InteractiveProcess;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;

class MysqlCommand
{
    /**
     * @var array
     */
    private $dbConfig;

    /**
     * @var string
     */
    private static $mysqlTempFile;

    /**
     * @var ConsoleOutput
     */
    private $output;

    public function __construct(array $dbConfig, ConsoleOutput $output = null)
    {
        $this->dbConfig = $dbConfig;
        $this->output = $output ?: new ConsoleOutput(); // output being optional is @deprecated. Will become required in 6.0
    }

    /**
     * @param array $additionalArguments
     * @param resource $inputStream
     * @param null $outputCallback @deprecated will be removed with 6.0
     * @param bool $interactive
     * @return int
     */
    public function mysql(array $additionalArguments = [], $inputStream = STDIN, $outputCallback = null, $interactive = false)
    {
        $commandLine = ['mysql'];
        $commandLine = array_merge($commandLine, $this->buildConnectionArguments(), $additionalArguments);
        $process = new Process($commandLine, null, null, $inputStream, 0.0);
        if ($interactive) {
            // I did not figure out how to change pipes with symfony/process
            $interactiveProcess = new InteractiveProcess();

            return $interactiveProcess->run($process->getCommandLine());
        }

        return $process->run($this->buildDefaultOutputCallback($outputCallback));
    }

    /**
     * @param array $additionalArguments
     * @param callable|null $outputCallback @deprecated will be removed with 6.0
     * @param string $connectionName
     * @return int
     */
    public function mysqldump(array $additionalArguments = [], $outputCallback = null, string $connectionName = 'Default'): int
    {
        $commandLine = ['mysqldump'];
        $commandLine = array_merge($commandLine, $this->buildConnectionArguments(), $additionalArguments);
        $process = new Process($commandLine, null, null, null, 0.0);

        echo  chr(10) . sprintf('-- Dump of TYPO3 Connection "%s"', $connectionName) . chr(10);

        return $process->run($this->buildDefaultOutputCallback($outputCallback));
    }

    /**
     * @param callable $outputCallback
     * @return callable
     */
    private function buildDefaultOutputCallback($outputCallback): callable
    {
        if (!is_callable($outputCallback)) {
            $outputCallback = function ($type, $data) {
                if (Process::OUT === $type) {
                    echo $data;
                } elseif (Process::ERR === $type) {
                    $this->output->getErrorOutput()->write($data);
                }
            };
        }

        return $outputCallback;
    }

    private function buildConnectionArguments(): array
    {
        if ($configFile = $this->createTemporaryMysqlConfigurationFile()) {
            $arguments[] = '--defaults-file=' . $configFile;
        }
        if (!empty($this->dbConfig['host'])) {
            $arguments[] = '-h';
            $arguments[] = $this->dbConfig['host'];
        }
        if (!empty($this->dbConfig['port'])) {
            $arguments[] = '-P';
            $arguments[] = $this->dbConfig['port'];
        }
        if (!empty($this->dbConfig['unix_socket'])) {
            $arguments[] = '-S';
            $arguments[] = $this->dbConfig['unix_socket'];
        }
        $arguments[] = $this->dbConfig['dbname'];

        return $arguments;
    }

    private function createTemporaryMysqlConfigurationFile()
    {
        if (empty($this->dbConfig['user']) && !isset($this->dbConfig['password'])) {
            return null;
        }
        if (self::$mysqlTempFile !== null && file_exists(self::$mysqlTempFile)) {
            return self::$mysqlTempFile;
        }
        $userDefinition = '';
        $passwordDefinition = '';
        if (!empty($this->dbConfig['user'])) {
            $userDefinition = sprintf('user="%s"', addcslashes($this->dbConfig['user'], '"\\'));
        }
        if (!empty($this->dbConfig['password'])) {
            $passwordDefinition = sprintf('password="%s"', addcslashes($this->dbConfig['password'], '"\\'));
        }
        $confFileContent = <<<EOF
[mysqldump]
$userDefinition
$passwordDefinition

[client]
$userDefinition
$passwordDefinition
EOF;
        self::$mysqlTempFile = tempnam(sys_get_temp_dir(), 'typo3_console_my_cnf_');
        file_put_contents(self::$mysqlTempFile, $confFileContent);
        register_shutdown_function('unlink', self::$mysqlTempFile);

        return self::$mysqlTempFile;
    }
}
