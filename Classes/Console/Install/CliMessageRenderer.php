<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Install;

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
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

class CliMessageRenderer
{
    /**
     * @var ConsoleOutput
     */
    private $output;

    private array $severityMap = [];

    public function __construct(ConsoleOutput $output)
    {
        $this->output = $output;
        if ((new Typo3Version())->getMajorVersion() > 12) {
            $this->severityMap = [
                ContextualFeedbackSeverity::ERROR->value => 'error',
                ContextualFeedbackSeverity::WARNING->value => 'warning',
            ];
        } else {
            $this->severityMap = [
                AbstractMessage::ERROR => 'error',
                AbstractMessage::WARNING => 'warning',
            ];
        }
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

    private function renderSingle($statusMessage)
    {
        $severity = $this->severityMap[$statusMessage['severity']] ?? 'notice';
        $subject = strtoupper($severity) . ': ' . ($statusMessage['title'] ?? '');
        switch ($severity) {
            case 'error':
            case 'warning':
                $subject = sprintf('<%1$s>' . $subject . '</%1$s>', $severity);
                break;
            default:
        }
        $this->output->outputLine($subject);
        foreach (explode("\n", wordwrap($statusMessage['message'])) as $line) {
            $this->output->outputLine(sprintf('<%1$s>' . $line . '</%1$s>', $severity));
        }
    }
}
