<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Error;

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

use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Global error handler for TYPO3 Console
 */
class ErrorHandler
{
    /**
     * @var int
     */
    private $errorsToHandle;

    /**
     * @var int
     */
    private $exceptionalErrors;

    /**
     * Sets which error should be handled by the error handler
     *
     * @param int $errorsToHandle
     */
    public function setErrorsToHandle(int $errorsToHandle)
    {
        $this->errorsToHandle = $errorsToHandle;
    }

    /**
     * Defines which error levels result should result in an exception thrown.
     *
     * @param int $exceptionalErrors E_* error levels
     */
    public function setExceptionalErrors(int $exceptionalErrors)
    {
        $this->exceptionalErrors = $exceptionalErrors;
    }

    /**
     * Converts error level int to friendly error type text
     *
     * @param int $errorLevel The error level - one of the E_* constants
     * @return string Error level as text
     */
    public function errorLevelToText(int $errorLevel): string
    {
        switch ($errorLevel) {
            case E_ERROR: // 1 //
                return 'Error';
            case E_WARNING: // 2 //
                return 'Warning';
            case E_PARSE: // 4 //
                return 'Parse errors';
            case E_NOTICE: // 8 //
                return 'Runtime notices';
            case E_CORE_ERROR: // 16 //
                return 'Core Fatal Error';
            case E_CORE_WARNING: // 32 //
                return 'Core Warning';
            case E_COMPILE_ERROR: // 64 //
                return 'Compile Error';
            case E_COMPILE_WARNING: // 128 //
                return 'Compile Warning';
            case E_USER_ERROR: // 256 //
                return 'User Error';
            case E_USER_WARNING: // 512 //
                return 'User Warning';
            case E_USER_NOTICE: // 1024 //
                return 'User Notice';
            case E_STRICT: // 2048 //
                return 'Runtime Notice';
            case E_RECOVERABLE_ERROR: // 4096 //
                return 'Catchable Fatal Error';
            case E_DEPRECATED: // 8192 //
                return 'Deprecation Notice';
            case E_USER_DEPRECATED: // 16384 //
                return 'User Deprecation Notice';
            default:
                return 'Unknown error level';
        }
    }

    /**
     * Handles an error by converting it into an exception.
     *
     * If error reporting is disabled, either in the php.ini or temporarily through
     * the shut-up operator "@", no exception will be thrown.
     *
     * @param int $errorLevel The error level - one of the E_* constants
     * @param string $errorMessage The error message
     * @param string $errorFile Name of the file the error occurred in
     * @param int $errorLine Line number where the error occurred
     * @throws \TYPO3\CMS\Core\Error\Exception with the data passed to this method
     * @return bool
     */
    public function handleError($errorLevel, $errorMessage, $errorFile, $errorLine): bool
    {
        $configuredErrorReporting = error_reporting();
        if (($configuredErrorReporting & $errorLevel) === 0 || ($errorLevel & $this->errorsToHandle) === 0) {
            return false;
        }

        if ($errorLevel & $this->exceptionalErrors) {
            throw new \TYPO3\CMS\Core\Error\Exception($this->errorLevelToText($errorLevel) . ': ' . $errorMessage . ' in ' . $errorFile . ' line ' . $errorLine, 1);
        }

        if ($errorLevel === E_USER_DEPRECATED) {
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger('TYPO3.CMS.deprecations');
            $logger->notice($errorMessage, ['file' => $errorFile, 'line' => $errorLine]);

            return true;
        }

        // Since all other severities are enforced to throw an exception (see: \Helhum\Typo3Console\Core\Booting\Scripts::initializeErrorHandling)
        // we can just log a notice here.
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $logger->notice($errorMessage, ['file' => $errorFile, 'line' => $errorLine]);

        return true;
    }
}
