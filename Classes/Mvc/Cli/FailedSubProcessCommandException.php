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
use Symfony\Component\Process\Process;

/**
 * Thrown when a sub process command failed
 */
class FailedSubProcessCommandException extends \Exception
{
    /**
     * @var string
     */
    private $command;

    /**
     * @var int
     */
    private $exitCode;

    /**
     * @var string
     */
    private $errorMessage;

    /**
     * @param string $command
     * @param Process $process
     * @return static
     */
    public static function forProcess($command, Process $process)
    {
        return new static($command, $process->getCommandLine(), $process->getExitCode(), str_replace("\r\n", "\n", trim($process->getOutput())), str_replace("\r\n", "\n", trim($process->getErrorOutput())));
    }

    /**
     * FailedSubProcessCommandException constructor.
     *
     * @param string $command
     * @param string $commandLine
     * @param int $exitCode
     * @param string $outputMessage
     * @param string $errorMessage
     */
    public function __construct($command, $commandLine, $exitCode, $outputMessage, $errorMessage)
    {
        if (empty($outputMessage . $errorMessage)) {
            $errorMessage = sprintf(
                "Executing \"%s\" failed (exit code: \"%d\") with no message\n",
                $command,
                $exitCode
            );
        } else {
            $errorMessage = sprintf(
                "Executing \"%s\" failed (exit code: \"%d\") with message:\n\n\"%s\"\n\nand error:\n\n\"%s\"\n",
                $command,
                $exitCode,
                $outputMessage,
                $errorMessage
            );
        }
        parent::__construct(
            $errorMessage,
            1485130941
        );
        $this->command = $command;
        $this->exitCode = $exitCode;
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return int
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
