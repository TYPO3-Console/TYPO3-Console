<?php
declare(strict_types=1);
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
     * @var string
     */
    private $commandLine;

    /**
     * @var int
     */
    private $exitCode;

    /**
     * @var string
     */
    private $outputMessage;

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
        $previousExceptionData = @\json_decode($errorMessage, true) ?: null;
        $previousException = null;
        if ($previousExceptionData) {
            $errorMessage = '';
            $previousException = SubProcessException::createFromArray($previousExceptionData);
        }
        $exceptionMessage = sprintf(
            'Executing command "%s" failed (exit code: "%d")',
            $command,
            $exitCode
        );
        parent::__construct(
            $exceptionMessage,
            1485130941,
            $previousException
        );
        $this->command = $command;
        $this->commandLine = $commandLine;
        $this->exitCode = $exitCode;
        $this->errorMessage = $errorMessage;
        $this->outputMessage = $outputMessage;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @return string
     */
    public function getCommandLine(): string
    {
        return $this->commandLine;
    }

    /**
     * @return int
     */
    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    /**
     * @return string
     */
    public function getOutputMessage(): string
    {
        return $this->outputMessage;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
