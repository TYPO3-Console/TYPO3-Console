<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Database\Process;

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

use Helhum\Typo3Console\Database\Configuration\MysqlCliConfiguration;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Helhum\Typo3Console\Mvc\Cli\InteractiveProcess;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class MysqlCommand
{
    public function __construct(
        private readonly MysqlCliConfiguration $connectionConfiguration,
        private readonly OutputInterface $output
    ) {
    }

    /**
     * @param resource $inputStream
     */
    public function mysql(array $additionalArguments = [], $inputStream = STDIN, bool $interactive = false): int
    {
        $commandLine = $this->connectionConfiguration->buildArguments($additionalArguments);
        $this->debugCommandLine($commandLine);
        $process = new Process($commandLine, null, null, $inputStream, 0.0);
        if ($interactive) {
            // I did not figure out how to change pipes with symfony/process
            return (new InteractiveProcess())->run($process->getCommandLine());
        }

        return $process->run($this->buildDefaultOutputCallback());
    }

    public function mysqldump(array $additionalArguments = [], array $excludes = []): int
    {
        $commandLine = $this->connectionConfiguration->buildArguments($additionalArguments, $excludes);
        $this->debugCommandLine($commandLine);
        $process = new Process($commandLine, null, null, null, 0.0);

        $this->output->write(chr(10) . sprintf('-- Dump of TYPO3 Connection "%s"', $this->connectionConfiguration->name) . chr(10), false, OutputInterface::OUTPUT_RAW);

        return $process->run($this->buildDefaultOutputCallback());
    }

    private function debugCommandLine(array $commandLine): void
    {
        if ($this->output instanceof ConsoleOutputInterface
            && $this->output->isVeryVerbose()
        ) {
            $formattedOutput = (new ConsoleOutput($this->output))->getSymfonyConsoleOutput();
            $formattedOutput->getErrorOutput()->writeln(
                $formattedOutput->getFormatter()->format(sprintf('Executing command: <code>%s</code> ', implode(' ', $commandLine)))
            );
        }
    }

    /**
     * @return callable
     */
    private function buildDefaultOutputCallback(): callable
    {
        return function ($type, $data) {
            if (Process::OUT === $type) {
                $this->output->write($data, false, OutputInterface::OUTPUT_RAW);
            } elseif (Process::ERR === $type) {
                $errorOutput = $this->output instanceof ConsoleOutputInterface
                    ? $this->output->getErrorOutput()
                    : $this->output;
                $errorOutput->write($data);
            }
        };
    }
}
