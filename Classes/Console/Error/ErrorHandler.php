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

        $errorLevels = [
            E_WARNING => 'Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Runtime Notice',
            E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
            E_USER_DEPRECATED => 'Deprecation Notice',
        ];

        if ($errorLevel & $this->exceptionalErrors) {
            throw new \TYPO3\CMS\Core\Error\Exception($errorLevels[$errorLevel] . ': ' . $errorMessage . ' in ' . $errorFile . ' line ' . $errorLine, 1);
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
