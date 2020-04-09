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

use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;
use Helhum\Typo3Console\Mvc\Cli\SubProcessException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExceptionRenderer
{
    /**
     * @var Terminal
     */
    private $terminal;

    public function __construct(Terminal $terminal = null)
    {
        $this->terminal = $terminal ?: new Terminal();
    }

    /**
     * Renders Exception with trace and nested exceptions with trace.
     *
     * @param \Throwable $exception
     * @param OutputInterface $output
     * @param Application|null $application
     */
    public function render(\Throwable $exception, OutputInterface $output, Application $application = null)
    {
        $this->writeLog($exception);
        if (getenv('TYPO3_CONSOLE_SUB_PROCESS')) {
            $output->write(\json_encode($this->serializeException($exception)), false, OutputInterface::VERBOSITY_QUIET);

            return;
        }
        $output->writeln('', OutputInterface::VERBOSITY_QUIET);
        do {
            $this->outputException($exception, $output);
            if ($output->isVerbose()) {
                $this->outputCode($exception, $output);
                $this->outputCommand($exception, $output);
                $this->outputTrace($exception, $output);
                $output->writeln('');
            }
            $exception = $exception->getPrevious();
            if ($exception) {
                $output->writeln('<comment>Caused by:</comment>', OutputInterface::VERBOSITY_QUIET);
            }
        } while ($exception);

        $this->outputSynopsis($output, $application);
    }

    private function writeLog(\Throwable $exception)
    {
        if (empty($GLOBALS['TYPO3_CONF_VARS']['LOG'])) {
            return;
        }
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $logger->critical($exception->getMessage(), ['exception' => $exception]);
    }

    /**
     * Output formatted exception.
     *
     * @param \Throwable $exception
     * @param OutputInterface $output
     */
    private function outputException(\Throwable $exception, OutputInterface $output)
    {
        $exceptionClass = get_class($exception);
        $exceptionMessage = $exception->getMessage();
        if ($exception instanceof SubProcessException) {
            $exceptionClass = $exception->getPreviousExceptionClass();
            $exceptionMessage = $exception->getPreviousExceptionMessage();
        }

        $title = sprintf('[ %s ]', $exceptionClass);

        $messageLength = Helper::strlen($title);
        $maxWidth = $this->terminal->getWidth() ? $this->terminal->getWidth() - 1 : PHP_INT_MAX;

        $lines = [];
        foreach (preg_split('/\r?\n/', trim($exceptionMessage)) as $line) {
            foreach ($this->splitStringByWidth($line, $maxWidth - 4) as $splitLine) {
                $lines[] = $splitLine;
                $messageLength = max(Helper::strlen($splitLine), $messageLength);
            }
        }

        $messages = [];
        $messages[] = $emptyLine = $this->padMessage('', $messageLength);
        $messages[] = $this->padMessage($title, $messageLength);
        foreach ($lines as $line) {
            $messages[] = $this->padMessage(OutputFormatter::escape($line), $messageLength);
        }
        $messages[] = $emptyLine;
        $messages[] = '';
        $output->writeln($messages, OutputInterface::VERBOSITY_QUIET);
    }

    /**
     * @param \Throwable $exception
     * @param OutputInterface $output
     */
    private function outputCode(\Throwable $exception, OutputInterface $output)
    {
        $code = $exception->getCode();
        if ($exception instanceof SubProcessException) {
            $code = $exception->getPreviousExceptionCode();
        }
        if (!empty($code)) {
            $output->writeln(sprintf('<comment>Exception code:</comment> <info>%s</info>', $code));
            $output->writeln('');
        }
    }

    /**
     * @param \Throwable $exception
     * @param OutputInterface $output
     */
    private function outputCommand(\Throwable $exception, OutputInterface $output)
    {
        if ($exception instanceof FailedSubProcessCommandException || ($exception instanceof SubProcessException && $exception->getCommandLine())) {
            $output->writeln('<comment>Command line:</comment>');
            $output->writeln($exception->getCommandLine());
            $output->writeln('');
            if ($exception->getOutputMessage()) {
                $output->writeln('<comment>Command output:</comment>');
                $output->writeln($exception->getOutputMessage());
                $output->writeln('');
            }
            if ($exception->getErrorMessage()) {
                $output->writeln('<comment>Command error output:</comment>');
                $output->writeln($exception->getErrorMessage());
                $output->writeln('');
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
        $backtraceSteps = $this->getTrace($exception);
        foreach ($backtraceSteps as $index => $step) {
            $traceLine = '#' . $index . ' ';
            if (isset($backtraceSteps[$index]['class'])) {
                $traceLine .= $backtraceSteps[$index]['class'];
            }
            if (isset($backtraceSteps[$index]['function'])) {
                $traceLine .= (isset($backtraceSteps[$index]['class']) ? $backtraceSteps[$index]['type'] : '') . $backtraceSteps[$index]['function'] . '()';
            }
            $output->writeln(sprintf('<info>%s</info>', $traceLine));
            if (isset($backtraceSteps[$index]['file'])) {
                $output->writeln('   ' . $this->getPossibleShortenedFileName($backtraceSteps[$index]['file']) . (isset($backtraceSteps[$index]['line']) ? ':' . $backtraceSteps[$index]['line'] : ''));
            }
        }
    }

    private function outputSynopsis(OutputInterface $output, Application $application = null)
    {
        if (!$application || getenv('TYPO3_CONSOLE_SUB_PROCESS')) {
            return;
        }
        \Closure::bind(function () use (&$runningCommand, $application) {
            $property = 'runningCommand';
            $runningCommand = $application->$property;
        }, null, Application::class)();

        if ($runningCommand !== null) {
            $output->writeln(sprintf('<info>%s</info>', sprintf($runningCommand->getSynopsis(), $application->getName())), OutputInterface::VERBOSITY_QUIET);
            $output->writeln('', OutputInterface::VERBOSITY_QUIET);
        }
    }

    private function splitStringByWidth($string, $width)
    {
        // str_split is not suitable for multi-byte characters, we should use preg_split to get char array properly.
        // additionally, array_slice() is not enough as some character has doubled width.
        // we need a function to split string not by character count but by string width
        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return str_split($string, $width);
        }

        $utf8String = mb_convert_encoding($string, 'utf8', $encoding);
        $lines = [];
        $line = '';
        foreach (preg_split('//u', $utf8String) as $char) {
            // test if $char could be appended to current line
            if (mb_strwidth($line . $char, 'utf8') <= $width) {
                $line .= $char;
                continue;
            }
            // if not, push current line to array and make new line
            $lines[] = str_pad($line, $width);
            $line = $char;
        }

        $lines[] = count($lines) ? str_pad($line, $width) : $line;
        mb_convert_variables($encoding, 'utf8', $lines);

        return $lines;
    }

    /**
     * Right pad message.
     *
     * @param string $message
     * @param int $maxLength
     * @return string
     */
    private function padMessage($message, $maxLength): string
    {
        return '<error>  ' . $message . str_pad('', $maxLength - strlen($message), ' ') . '  </error>';
    }

    /**
     * Shorten file name if inside extension or core extension.
     *
     * @param string $fileName
     * @return string
     */
    private function getPossibleShortenedFileName($fileName): string
    {
        $pathPrefixes = [];
        if (getenv('TYPO3_PATH_COMPOSER_ROOT')) {
            $pathPrefixes = [getenv('TYPO3_PATH_COMPOSER_ROOT') . '/'];
        }
        if (getenv('TYPO3_PATH_ROOT')) {
            $pathPrefixes[] = getenv('TYPO3_PATH_ROOT') . '/';
        }
        if (defined('PATH_site')) {
            $pathPrefixes[] = PATH_site;
        }
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
    private function serializeException(\Throwable $exception = null)
    {
        $serializedException = null;
        if ($exception) {
            $exceptionClass = get_class($exception);
            $exceptionMessage = $exception->getMessage();
            $exceptionCode = $exception->getCode();
            $line = $exception->getLine();
            $file = $exception->getFile();
            if ($exception instanceof SubProcessException) {
                $exceptionClass = $exception->getPreviousExceptionClass();
                $exceptionMessage = $exception->getPreviousExceptionMessage();
                $exceptionCode = $exception->getPreviousExceptionCode();
                $line = $exception->getPreviousExceptionLine();
                $file = $exception->getPreviousExceptionFile();
            } elseif ($exception instanceof FailedSubProcessCommandException) {
                $backtraceSteps = $exception->getTrace();
                $line = $backtraceSteps[1]['line'];
                $file = $backtraceSteps[1]['file'];
                $commandLine = $exception->getCommandLine();
                $outputMessage = $exception->getOutputMessage();
                $errorMessage = $exception->getErrorMessage();
            }
            $serializedException = [
                'class' => $exceptionClass,
                'line' => $line,
                'file' => $file,
                'message' => $exceptionMessage,
                'code' => $exceptionCode,
                'trace' => $this->getTrace($exception),
                'previous' => $this->serializeException($exception->getPrevious()),
                'commandline' => $commandLine ?? null,
                'output' => $outputMessage ?? null,
                'error' => $errorMessage ?? null,
            ];
        }

        return $serializedException;
    }

    private function getTrace(\Throwable $exception): array
    {
        $backtraceSteps = $exception->getTrace();
        if ($exception instanceof SubProcessException) {
            $backtraceSteps = $exception->getPreviousExceptionTrace();
        } elseif ($exception instanceof FailedSubProcessCommandException) {
            array_shift($backtraceSteps);
        } else {
            array_unshift($backtraceSteps, [
                'function' => '',
                'file' => $exception->getFile() ?: 'n/a',
                'line' => $exception->getLine() ?: 'n/a',
                'args' => [],
            ]);
        }

        return $backtraceSteps;
    }
}
