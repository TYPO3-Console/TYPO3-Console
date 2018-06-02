<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\TYPO3v87\Install;

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

use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use TYPO3\CMS\Install\Status\StatusInterface;

class CliMessageRenderer
{
    /**
     * @var ConsoleOutput
     */
    private $output;

    public function __construct(ConsoleOutput $output)
    {
        $this->output = $output;
    }

    public function render(array $messages)
    {
        if (empty($messages)) {
            return;
        }

        $this->output->outputLine();
        foreach ($messages as $statusMessage) {
            $this->renderSingle($statusMessage);
        }
    }

    private function renderSingle(StatusInterface $statusMessage)
    {
        $subject = strtoupper($statusMessage->getSeverity()) . ': ' . $statusMessage->getTitle();
        switch ($statusMessage->getSeverity()) {
            case 'error':
            case 'warning':
                $subject = sprintf('<%1$s>' . $subject . '</%1$s>', $statusMessage->getSeverity());
            break;
            default:
        }
        $this->output->outputLine($subject);
        foreach (explode("\n", wordwrap($statusMessage->getMessage())) as $line) {
            $this->output->outputLine(sprintf('<%1$s>' . $line . '</%1$s>', $statusMessage->getSeverity()));
        }
    }
}
