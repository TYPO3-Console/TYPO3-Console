<?php
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
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Class MysqlCommand
 */
class MysqlCommand
{
    /**
     * @var ProcessBuilder
     */
    protected $processBuilder;

    /**
     * @var array
     */
    protected $dbConfig = [];

    /**
     * MysqlCommand constructor.
     *
     * @param array $dbConfig
     * @param ProcessBuilder $processBuilder
     */
    public function __construct(array $dbConfig, ProcessBuilder $processBuilder)
    {
        $this->dbConfig = $dbConfig;
        $this->processBuilder = $processBuilder;
        $this->processBuilder->setTimeout(null);
    }

    /**
     * @param array $additionalArguments
     * @param resource $inputStream
     * @param null $outputCallback
     * @param bool $interactive
     * @return int
     */
    public function mysql(array $additionalArguments = [], $inputStream = STDIN, $outputCallback = null, $interactive = false)
    {
        $this->processBuilder->setPrefix('mysql');
        $this->processBuilder->setArguments(array_merge($this->buildConnectionArguments(), $additionalArguments));
        if ($interactive) {
            // I did not figure out how to change pipes with symfony/process
            $interactiveProcess = new InteractiveProcess();
            return $interactiveProcess->run($this->processBuilder->getProcess()->getCommandLine());
        }
        $process = $this->processBuilder->getProcess();
        $process->setInput($inputStream);
        return $process->run($this->buildDefaultOutputCallback($outputCallback));
    }

    /**
     * @param array $additionalArguments
     * @param null $outputCallback
     * @return int
     */
    public function mysqldump(array $additionalArguments = [], $outputCallback = null)
    {
        $this->processBuilder->setPrefix('mysqldump');
        $this->processBuilder->setArguments(array_merge($this->buildConnectionArguments(), $additionalArguments));
        $process = $this->processBuilder->getProcess();
        return $process->run($this->buildDefaultOutputCallback($outputCallback));
    }

    /**
     * @param callable $outputCallback
     * @return callable
     */
    protected function buildDefaultOutputCallback($outputCallback)
    {
        if (!is_callable($outputCallback)) {
            $outputCallback = function ($type, $output) {
                if (Process::OUT === $type) {
                    // Explicitly just echo out for now (avoid symfony console formatting)
                    echo $output;
                }
            };
        }
        return $outputCallback;
    }

    protected function buildConnectionArguments()
    {
        if (!empty($this->dbConfig['user'])) {
            $arguments[] = '-u';
            $arguments[] = $this->dbConfig['user'];
        }
        if (!empty($this->dbConfig['password'])) {
            $arguments[] = '-p' . $this->dbConfig['password'];
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
}
