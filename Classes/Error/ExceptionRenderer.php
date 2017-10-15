<?php
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

use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;
use Helhum\Typo3Console\Mvc\Cli\SubProcessException;
use Symfony\Component\Console\Output\OutputInterface;

class ExceptionRenderer
{
    /**
     * Renders Exception with trace and nested exceptions with trace.
     *
     * @param \Throwable $exception
     * @param OutputInterface $output
     */
    public function render(\Throwable $exception, OutputInterface $output)
    {
        if (getenv('TYPO3_CONSOLE_SUB_PROCESS')) {
            $output->write(\json_encode($this->serializeException($exception)));
            return;
        }
        $output->writeln('');
        $this->outputException($exception, $output);
        $output->writeln('');
        $this->outputTrace($exception, $output);
        $previousException = $exception;
        $level = 0;
        while (($previousException = $previousException->getPrevious()) !== null) {
            $level++;
            $output->writeln('');
            $this->outputException($previousException, $output, $level);
            $output->writeln('');
            $this->outputTrace($previousException, $output);
        }
    }

    /**
     * Output formatted exception.
     *
     * @param \Throwable $exception
     * @param OutputInterface $output
     * @param int $level
     */
    private function outputException(\Throwable $exception, OutputInterface $output, $level = 0)
    {
        $exceptionCodeNumber = ($exception->getCode() > 0) ? '#' . $exception->getCode() . ': ' : '';
        $exceptionClass = get_class($exception);
        $line = $exception->getLine();
        $file = $exception->getFile();
        if ($exception instanceof SubProcessException) {
            if ($level > 1) {
                $exceptionClass = str_replace('Sub-process exception: ', sprintf('Sub-process exception (%d): ', $level), $exception->getPreviousExceptionClass());
            } else {
                $exceptionClass = 'Sub-process exception: ' . $exception->getPreviousExceptionClass();
            }
            $line = $exception->getPreviousExceptionLine();
            $file = $exception->getPreviousExceptionFile();
        } elseif ($exception instanceof FailedSubProcessCommandException) {
            $backtraceSteps = $exception->getTrace();
            $line = $backtraceSteps[1]['line'];
            $file = $backtraceSteps[1]['file'];
        }

        $title = sprintf('[ %s ]', $exceptionClass);
        $exceptionTitle = sprintf('%s%s', $exceptionCodeNumber, $exception->getMessage());
        $exceptionFile = sprintf('thrown in file %s', $this->getPossibleShortenedFileName($file));
        $exceptionLine = sprintf('in line %s', $line);

        $maxLength = max([strlen($title), strlen($exceptionTitle), strlen($exceptionFile), strlen($exceptionLine)]);
        $output->writeln($this->padMessage('', $maxLength));
        $output->writeln($this->padMessage($title, $maxLength));
        $output->writeln($this->padMessage($exceptionTitle, $maxLength));
        $output->writeln($this->padMessage($exceptionFile, $maxLength));
        $output->writeln($this->padMessage($exceptionLine, $maxLength));
        $output->writeln($this->padMessage('', $maxLength));
        if ($exception instanceof FailedSubProcessCommandException) {
            $output->writeln('');
            $output->writeln('<comment>Command line:</comment>');
            $output->writeln($exception->getCommandLine());
            if ($exception->getOutputMessage()) {
                $output->writeln('');
                $output->writeln('<comment>Command output:</comment>');
                $output->writeln($exception->getOutputMessage());
            }
            if ($exception->getErrorMessage()) {
                $output->writeln('');
                $output->writeln('<comment>Command error output:</comment>');
                $output->writeln($exception->getErrorMessage());
            }
        }
    }

    /**
     * Output formatted trace.
     *
     * @param \Throwable $exception
     */
    private function outputTrace(\Throwable $exception, OutputInterface $output)
    {
        $output->writeln('<comment>Exception trace:</comment>');
        $backtraceSteps = $exception->getTrace();
        if ($exception instanceof SubProcessException) {
            $backtraceSteps = $exception->getPreviousExceptionTrace();
        } elseif ($exception instanceof FailedSubProcessCommandException) {
            array_shift($backtraceSteps);
        }
        foreach ($backtraceSteps as $index => $step) {
            $traceLine = '#' . $index . ' ';
            if (isset($backtraceSteps[$index]['class'])) {
                $traceLine .= $backtraceSteps[$index]['class'];
            }
            if (isset($backtraceSteps[$index]['function'])) {
                $traceLine .= (isset($backtraceSteps[$index]['class']) ? '::' : '') . $backtraceSteps[$index]['function'] . '()';
            }
            $output->writeln(sprintf('<info>%s</info>', $traceLine));
            if (isset($backtraceSteps[$index]['file'])) {
                $output->writeln('   ' . $this->getPossibleShortenedFileName($backtraceSteps[$index]['file']) . (isset($backtraceSteps[$index]['line']) ? ':' . $backtraceSteps[$index]['line'] : ''));
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
    private function padMessage($message, $maxLength)
    {
        return '<error> ' . $message . str_pad('', $maxLength - strlen($message), ' ') . ' </error>';
    }

    /**
     * Shorten file name if inside extension or core extension.
     *
     * @param string $fileName
     * @return string
     */
    private function getPossibleShortenedFileName($fileName)
    {
        $pathPrefixes = [];
        if (getenv('TYPO3_PATH_COMPOSER_ROOT')) {
            $pathPrefixes = [getenv('TYPO3_PATH_COMPOSER_ROOT') . '/'];
        }
        $pathPrefixes[] = PATH_site;
        $fileName = str_replace($pathPrefixes, '', $fileName);
        $pathPosition = strpos($fileName, 'typo3conf/ext/');
        $pathAndFilename = ($pathPosition !== false) ? substr($fileName, $pathPosition) : $fileName;
        $pathPosition = strpos($pathAndFilename, 'typo3/sysext/');
        return ($pathPosition !== false) ? substr($pathAndFilename, $pathPosition) : $pathAndFilename;
    }

    /**
     * @param \Throwable $exception
     * @return array|null
     */
    private function serializeException(\Throwable $exception)
    {
        $serializedException = null;
        if ($exception) {
            $exceptionClass = get_class($exception);
            $line = $exception->getLine();
            $file = $exception->getFile();
            if ($exception instanceof SubProcessException) {
                $exceptionClass = 'Sub-process exception: ' . $exception->getPreviousExceptionClass();
                $line = $exception->getPreviousExceptionLine();
                $file = $exception->getPreviousExceptionFile();
            } elseif ($exception instanceof FailedSubProcessCommandException) {
                $backtraceSteps = $exception->getTrace();
                $line = $backtraceSteps[1]['line'];
                $file = $backtraceSteps[1]['file'];
            }
            $serializedException = [
                'class' => $exceptionClass,
                'line' => $line,
                'file' => $file,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'trace' => $exception->getTrace(),
                'previous' => $this->serializeException($exception->getPrevious()),
            ];
        }
        return $serializedException;
    }
}
