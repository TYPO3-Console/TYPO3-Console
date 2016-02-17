<?php
namespace Helhum\Typo3Console\Error;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class ExceptionHandler
 */
class ExceptionHandler
{
    /**
     * Register Exception Handler
     */
    public function __construct()
    {
        set_exception_handler(array($this, 'handleException'));
    }

    /**
     * Formats and echoes the exception for the command line
     *
     * @param \Exception $exception The exception object
     * @return void
     */
    public function handleException($exception)
    {
        $pathPosition = strpos($exception->getFile(), 'ext/');
        $filePathAndName = ($pathPosition !== false) ? substr($exception->getFile(), $pathPosition) : $exception->getFile();

        $exceptionCodeNumber = ($exception->getCode() > 0) ? '#' . $exception->getCode() . ': ' : '';

        echo PHP_EOL . 'Uncaught Exception in TYPO3 CMS ' . $exceptionCodeNumber . $exception->getMessage() . PHP_EOL;
        echo 'thrown in file ' . $filePathAndName . PHP_EOL;
        echo 'in line ' . $exception->getLine() . PHP_EOL;
        if ($exception instanceof \TYPO3\Flow\Exception) {
            echo 'Reference code: ' . $exception->getReferenceCode() . PHP_EOL;
        }

        $indent = '  ';
        while (($exception = $exception->getPrevious()) !== null) {
            echo PHP_EOL . $indent . 'Nested exception:' . PHP_EOL;
            $pathPosition = strpos($exception->getFile(), 'Packages/');
            $filePathAndName = ($pathPosition !== false) ? substr($exception->getFile(), $pathPosition) : $exception->getFile();

            $exceptionCodeNumber = ($exception->getCode() > 0) ? '#' . $exception->getCode() . ': ' : '';

            echo PHP_EOL . $indent . 'Uncaught Exception in Flow ' . $exceptionCodeNumber . $exception->getMessage() . PHP_EOL;
            echo $indent . 'thrown in file ' . $filePathAndName . PHP_EOL;
            echo $indent . 'in line ' . $exception->getLine() . PHP_EOL;
            if ($exception instanceof \TYPO3\Flow\Exception) {
                echo 'Reference code: ' . $exception->getReferenceCode() . PHP_EOL;
            }

            $indent .= '  ';
        }

        if (function_exists('xdebug_get_function_stack')) {
            $backtraceSteps = xdebug_get_function_stack();
        } else {
            $backtraceSteps = debug_backtrace();
        }

        for ($index = 0; $index < count($backtraceSteps); $index ++) {
            echo PHP_EOL . '#' . $index . ' ';
            if (isset($backtraceSteps[$index]['class'])) {
                echo $backtraceSteps[$index]['class'];
            }
            if (isset($backtraceSteps[$index]['function'])) {
                echo '::' . $backtraceSteps[$index]['function'] . '()';
            }
            echo PHP_EOL;
            if (isset($backtraceSteps[$index]['file'])) {
                echo '   ' . $backtraceSteps[$index]['file'] . (isset($backtraceSteps[$index]['line']) ? ':' . $backtraceSteps[$index]['line'] : '') . PHP_EOL;
            }
        }

        echo PHP_EOL;
        exit(1);
    }
}
