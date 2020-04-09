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

/**
 * Thrown when a sub process command failed
 */
class SubProcessException extends \Exception
{
    /**
     * @var string
     */
    private $previousExceptionClass;

    /**
     * @var string|null
     */
    private $previousExceptionMessage;

    /**
     * @var mixed
     */
    private $previousExceptionCode;

    /**
     * @var string|null
     */
    private $previousExceptionTrace;

    /**
     * @var string|null
     */
    private $previousExceptionLine;

    /**
     * @var string|null
     */
    private $previousExceptionFile;

    /**
     * @var string|null
     */
    private $previousExceptionCommandLine;

    /**
     * @var string|null
     */
    private $previousExceptionOutputMessage;

    /**
     * @var string|null
     */
    private $previousExceptionErrorMessage;

    public function __construct(
        $previousExceptionClass,
        $previousExceptionMessage,
        $previousExceptionCode,
        $previousExceptionTrace,
        $previousExceptionLine,
        $previousExceptionFile,
        $previousExceptionData = null,
        $previousExceptionCommandLine = null,
        $previousExceptionOutputMessage = null,
        $previousExceptionErrorMessage = null
    ) {
        $code = $previousExceptionCode;
        $message = $previousExceptionMessage;
        if ($previousExceptionCode !== (int)$previousExceptionCode) {
            $message = sprintf('[%s] %s', $previousExceptionCode, $previousExceptionMessage);
            $code = 0;
        }
        $previousException = $previousExceptionData ? self::createFromArray($previousExceptionData) : null;
        $fullMessage = sprintf('[%s] %s', $previousExceptionClass, $message);
        parent::__construct($fullMessage, $code, $previousException);
        $this->previousExceptionClass = $previousExceptionClass;
        $this->previousExceptionMessage = $previousExceptionMessage;
        $this->previousExceptionCode = $previousExceptionCode;
        $this->previousExceptionTrace = $previousExceptionTrace;
        $this->previousExceptionLine = $previousExceptionLine;
        $this->previousExceptionFile = $previousExceptionFile;
        $this->previousExceptionCommandLine = $previousExceptionCommandLine;
        $this->previousExceptionOutputMessage = $previousExceptionOutputMessage;
        $this->previousExceptionErrorMessage = $previousExceptionErrorMessage;

        $this->line = $previousExceptionLine;
        $this->file = $previousExceptionFile;
        array_shift($previousExceptionTrace);
        $traceReflection = new \ReflectionProperty(\Exception::class, 'trace');
        $traceReflection->setAccessible(true);
        $traceReflection->setValue($this, $previousExceptionTrace);
    }

    public static function createFromArray($previousExceptionData)
    {
        return new self(
            $previousExceptionData['class'],
            $previousExceptionData['message'],
            $previousExceptionData['code'],
            $previousExceptionData['trace'],
            $previousExceptionData['line'],
            $previousExceptionData['file'],
            $previousExceptionData['previous'],
            $previousExceptionData['commandline'],
            $previousExceptionData['output'],
            $previousExceptionData['error']
        );
    }

    /**
     * @return string
     */
    public function getPreviousExceptionClass()
    {
        return $this->previousExceptionClass;
    }

    /**
     * @return string|null
     */
    public function getPreviousExceptionMessage()
    {
        return $this->previousExceptionMessage;
    }

    /**
     * @return mixed
     */
    public function getPreviousExceptionCode()
    {
        return $this->previousExceptionCode;
    }

    /**
     * @return mixed
     */
    public function getPreviousExceptionTrace()
    {
        return $this->previousExceptionTrace;
    }

    /**
     * @return mixed
     */
    public function getPreviousExceptionLine()
    {
        return $this->previousExceptionLine;
    }

    /**
     * @return mixed
     */
    public function getPreviousExceptionFile()
    {
        return $this->previousExceptionFile;
    }

    /**
     * @return null
     */
    public function getCommandLine()
    {
        return $this->previousExceptionCommandLine;
    }

    /**
     * @return null
     */
    public function getOutputMessage()
    {
        return $this->previousExceptionOutputMessage;
    }

    /**
     * @return null
     */
    public function getErrorMessage()
    {
        return $this->previousExceptionErrorMessage;
    }
}
