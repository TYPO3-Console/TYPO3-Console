<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Log\Writer;

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

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;

/**
 * Class ConsoleWriter
 */
class ConsoleWriter extends AbstractWriter
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    protected $messageWrap = '|';

    protected $severityTagMapping = [
        LogLevel::EMERGENCY => '<error>|</error>',
        LogLevel::ALERT => '<error>|</error>',
        LogLevel::CRITICAL => '<error>|</error>',
        LogLevel::ERROR => '<fg=red>|</fg=red>',
        LogLevel::WARNING => '<fg=yellow;options=bold>|</fg=yellow;options=bold>',
        LogLevel::NOTICE => '<fg=yellow>|</fg=yellow>',
        LogLevel::INFO => '<info>|</info>',
        LogLevel::DEBUG => '|',
    ];

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param string $messageWrap
     */
    public function setMessageWrap($messageWrap)
    {
        $this->messageWrap = $messageWrap;
    }

    /**
     * Writes the log record
     *
     * @param \TYPO3\CMS\Core\Log\LogRecord $record Log record
     * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface $this
     */
    public function writeLog(\TYPO3\CMS\Core\Log\LogRecord $record)
    {
        $this->output->write(
            $this->wrapMessage(vsprintf($record->getMessage(), $record->getData()), $record->getLevel()),
            true
        );

        return $this;
    }

    protected function wrapMessage($message, $level)
    {
        list($tagStart, $tagEnd) = explode('|', $this->severityTagMapping[$level]);
        list($wrapStart, $wrapEnd) = explode('|', $this->messageWrap);

        return $tagStart . $wrapStart . $message . $wrapEnd . $tagEnd;
    }
}
