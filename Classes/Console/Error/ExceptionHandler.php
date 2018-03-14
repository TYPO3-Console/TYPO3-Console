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

use Symfony\Component\Console\Output\ConsoleOutput;

class ExceptionHandler
{
    /**
     * @var ExceptionRenderer
     */
    private $exceptionRenderer;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * @param ConsoleOutput $output
     */
    public function __construct(ExceptionRenderer $exceptionRenderer = null, ConsoleOutput $output = null)
    {
        $this->exceptionRenderer = $exceptionRenderer ?: new ExceptionRenderer();
        if ($output === null) {
            $output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);
        }
        $this->output = $output;
    }

    /**
     * Formats and echoes the exception for the command line.
     *
     * @param \Throwable $exception The exception object
     * @return void
     */
    public function handleException(\Throwable $exception)
    {
        $this->exceptionRenderer->render($exception, $this->output->getErrorOutput());

        echo PHP_EOL;
        exit(1);
    }
}
