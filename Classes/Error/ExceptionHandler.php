<?php
namespace Helhum\Typo3Console\Error;

/*
 * This file is part of the TYPO3 console project.
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
     * @param \Exception|\Throwable $exception The exception object
     * @return void
     */
    public function handleException($exception)
    {
        $this->outputSingleException($exception);
        $indent = '  ';
        while (($exception = $exception->getPrevious()) !== null) {
            echo PHP_EOL . $indent . 'Nested exception:' . PHP_EOL;
            $this->outputSingleException($exception, $indent);
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

    /**
     * @param \Exception|\Throwable $exception
     * @param string $indent
     */
    protected function outputSingleException($exception, $indent = '')
    {
        $pathPosition = strpos($exception->getFile(), 'ext/');
        $filePathAndName = ($pathPosition !== false) ? substr($exception->getFile(), $pathPosition) : $exception->getFile();
        $exceptionCodeNumber = ($exception->getCode() > 0) ? '#' . $exception->getCode() . ': ' : '';
        echo PHP_EOL . $indent . 'Uncaught Exception in TYPO3 CMS: ' . $exceptionCodeNumber . $exception->getMessage() . PHP_EOL;
        echo $indent . 'thrown in file ' . $filePathAndName . PHP_EOL;
        echo $indent . 'in line ' . $exception->getLine() . PHP_EOL;
    }
}
