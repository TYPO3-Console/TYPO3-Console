<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Mvc\Cli\Symfony\Output;

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

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class TrackableOutput extends ConsoleOutput
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var bool
     */
    private $outputTracked = false;

    /**
     * @var TrackableOutput
     */
    private $parentOutput;

    public function __construct(OutputInterface $output, ?self $parentOutput = null)
    {
        $this->output = $output;
        $this->parentOutput = $parentOutput;
        if ($output instanceof ConsoleOutput) {
            $output->setErrorOutput(new self($output->getErrorOutput(), $this));
        }
        parent::__construct();
    }

    public function startTracking(): void
    {
        $this->outputTracked = false;
    }

    public function emitOutputTracked(): void
    {
        $this->outputTracked = true;
    }

    /**
     * @return bool
     */
    public function wasOutputTracked(): bool
    {
        return $this->outputTracked;
    }

    public function getErrorOutput(): OutputInterface
    {
        if ($this->output instanceof ConsoleOutput) {
            return $this->output->getErrorOutput();
        }

        return new self($this->output, $this);
    }

    public function setErrorOutput(OutputInterface $error): void
    {
        parent::setErrorOutput(new self($error, $this));
    }

    public function write($messages, $newline = false, $options = self::OUTPUT_NORMAL): void
    {
        $this->outputTracked = true;
        if ($this->parentOutput) {
            $this->parentOutput->emitOutputTracked();
        }
        $this->output->write($messages, $newline, $options);
    }

    public function writeln($messages, $type = self::OUTPUT_NORMAL): void
    {
        $this->write($messages, true, $type);
    }

    public function setVerbosity($level): void
    {
        $this->output->setVerbosity($level);
    }

    public function getVerbosity(): int
    {
        return $this->output->getVerbosity();
    }

    public function setDecorated($decorated): void
    {
        $this->output->setDecorated($decorated);
    }

    public function isDecorated(): bool
    {
        return $this->output->isDecorated();
    }

    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        $this->output->setFormatter($formatter);
    }

    public function getFormatter(): OutputFormatterInterface
    {
        return $this->output->getFormatter();
    }

    public function isQuiet(): bool
    {
        return self::VERBOSITY_QUIET === $this->getVerbosity();
    }

    public function isVerbose(): bool
    {
        return self::VERBOSITY_VERBOSE <= $this->getVerbosity();
    }

    public function isVeryVerbose(): bool
    {
        return self::VERBOSITY_VERY_VERBOSE <= $this->getVerbosity();
    }

    public function isDebug(): bool
    {
        return self::VERBOSITY_DEBUG <= $this->getVerbosity();
    }
}
