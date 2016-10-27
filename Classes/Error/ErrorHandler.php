<?php

namespace Helhum\Typo3Console\Error;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Global error handler for Flow.
 */
class ErrorHandler
{
    /**
     * @var array
     */
    protected $exceptionalErrors = [];

    /**
     * Constructs this error handler - registers itself as the default error handler.
     */
    public function __construct()
    {
        set_error_handler([$this, 'handleError']);
    }

    /**
     * Defines which error levels result should result in an exception thrown.
     *
     * @param array $exceptionalErrors An array of E_* error levels
     *
     * @return void
     */
    public function setExceptionalErrors(array $exceptionalErrors)
    {
        $this->exceptionalErrors = $exceptionalErrors;
    }

    /**
     * Handles an error by converting it into an exception.
     *
     * If error reporting is disabled, either in the php.ini or temporarily through
     * the shut-up operator "@", no exception will be thrown.
     *
     * @param int    $errorLevel   The error level - one of the E_* constants
     * @param string $errorMessage The error message
     * @param string $errorFile    Name of the file the error occurred in
     * @param int    $errorLine    Line number where the error occurred
     *
     * @throws \TYPO3\CMS\Core\Error\Exception with the data passed to this method
     * @throws \Exception
     *
     * @return void
     */
    public function handleError($errorLevel, $errorMessage, $errorFile, $errorLine)
    {
        if (error_reporting() === 0) {
            return;
        }

        $errorLevels = [
            E_WARNING            => 'Warning',
            E_NOTICE             => 'Notice',
            E_USER_ERROR         => 'User Error',
            E_USER_WARNING       => 'User Warning',
            E_USER_NOTICE        => 'User Notice',
            E_STRICT             => 'Runtime Notice',
            E_RECOVERABLE_ERROR  => 'Catchable Fatal Error',
        ];

        if (in_array($errorLevel, (array) $this->exceptionalErrors)) {
            if (class_exists('TYPO3\\CMS\\Core\\Error\\Exception')) {
                throw new \TYPO3\CMS\Core\Error\Exception($errorLevels[$errorLevel].': '.$errorMessage.' in '.$errorFile.' line '.$errorLine, 1);
            } else {
                throw new \Exception($errorLevels[$errorLevel].': '.$errorMessage.' in '.$errorFile.' line '.$errorLine, 1);
            }
        }
    }
}
