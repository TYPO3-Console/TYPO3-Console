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

use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class ExceptionHandler
 */
class ExceptionHandler
{
    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * @param ConsoleOutput $output
     */
    public function __construct(ConsoleOutput $output = null)
    {
        $this->output = $output ?: new ConsoleOutput();
    }

    /**
     * Formats and echoes the exception for the command line.
     *
     * @param \Exception|\Throwable $exception The exception object
     * @return void
     */
    public function handleException($exception)
    {
        $this->renderException($exception);

        echo PHP_EOL;
        exit(1);
    }

    /**
     * Renders Exception with trace and nested exceptions with trace.
     *
     * @param \Exception|\Throwable $exception
     */
    protected function renderException($exception)
    {
        $this->output->writeln('');
        $this->outputException($exception);
        $this->output->writeln('');
        $this->outputTrace($exception);
        $previousException = $exception;
        while (($previousException = $previousException->getPrevious()) !== null) {
            $this->output->writeln('');
            $this->outputException($previousException);
            $this->output->writeln('');
            $this->outputTrace($previousException);
        }
    }

    /**
     * Output formatted exception.
     *
     * @param \Exception|\Throwable $exception
     */
    protected function outputException($exception)
    {
        $exceptionCodeNumber = ($exception->getCode() > 0) ? '#' . $exception->getCode() . ': ' : '';

        $title = sprintf('[ %s ]', get_class($exception));
        $exceptionTitle = sprintf('%s%s', $exceptionCodeNumber, $exception->getMessage());
        $exceptionFile = sprintf('thrown in file %s', $this->getPossibleShortenedFileName($exception->getFile()));
        $exceptionLine = sprintf('in line %s', $exception->getLine());

        $maxLength = max([strlen($title), strlen($exceptionTitle), strlen($exceptionFile), strlen($exceptionLine)]);
        $this->output->writeln($this->padMessage('', $maxLength));
        $this->output->writeln($this->padMessage($title, $maxLength));
        $this->output->writeln($this->padMessage($exceptionTitle, $maxLength));
        $this->output->writeln($this->padMessage($exceptionFile, $maxLength));
        $this->output->writeln($this->padMessage($exceptionLine, $maxLength));
        $this->output->writeln($this->padMessage('', $maxLength));
    }

    /**
     * Output formatted trace.
     *
     * @param \Exception|\Throwable $exception
     */
    protected function outputTrace($exception)
    {
        $this->output->writeln('<comment>Exception trace:</comment>');
        $backtraceSteps = $exception->getTrace();
        foreach ($backtraceSteps as $index => $step) {
            $traceLine = '#' . $index . ' ';
            if (isset($backtraceSteps[$index]['class'])) {
                $traceLine .= $backtraceSteps[$index]['class'];
            }
            if (isset($backtraceSteps[$index]['function'])) {
                $traceLine .= (isset($backtraceSteps[$index]['class']) ? '::' : '') . $backtraceSteps[$index]['function'] . '()';
            }
            $this->output->writeln(sprintf('<info>%s</info>', $traceLine));
            if (isset($backtraceSteps[$index]['file'])) {
                $this->output->writeln('   ' . $this->getPossibleShortenedFileName($backtraceSteps[$index]['file']) . (isset($backtraceSteps[$index]['line']) ? ':' . $backtraceSteps[$index]['line'] : ''));
            }
        }
    }

    /**
     * Right pad message.
     *
     * @param string $message
     * @param int $maxLength
     * @return string
     */
    protected function padMessage($message, $maxLength)
    {
        return '<error> ' . $message . str_pad('', $maxLength - strlen($message), ' ') . ' </error>';
    }

    /**
     * Shorten file name if inside extension or core extension.
     *
     * @param string $fileName
     * @return string
     */
    protected function getPossibleShortenedFileName($fileName)
    {
        $pathPosition = strpos($fileName, 'typo3conf/ext/');
        $pathAndFilename = ($pathPosition !== false) ? substr($fileName, $pathPosition) : $fileName;
        $pathPosition = strpos($pathAndFilename, 'typo3/sysext/');
        return ($pathPosition !== false) ? substr($pathAndFilename, $pathPosition) : $pathAndFilename;
    }
}
