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
     * @var
     */
    private $previousExceptionTrace;

    /**
     * @var
     */
    private $previousExceptionLine;

    /**
     * @var
     */
    private $previousExceptionFile;

    public function __construct($previousExceptionClass, $previousExceptionMessage, $previousExceptionCode, $previousExceptionTrace, $previousExceptionLine, $previousExceptionFile, $previousExceptionData = null)
    {
        $previousException = $previousExceptionData ? self::createFromArray($previousExceptionData) : null;
        parent::__construct($previousExceptionMessage, $previousExceptionCode, $previousException);
        $this->previousExceptionClass = $previousExceptionClass;
        $this->previousExceptionTrace = $previousExceptionTrace;
        $this->previousExceptionLine = $previousExceptionLine;
        $this->previousExceptionFile = $previousExceptionFile;
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
            $previousExceptionData['previous']
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
}
