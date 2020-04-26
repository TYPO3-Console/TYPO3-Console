<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Install\Upgrade;

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
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Terminal;
use TYPO3\CMS\Install\Updates\DatabaseRowsUpdateWizard;

/**
 * Renders a list of upgrade wizards
 */
class UpgradeWizardListRenderer
{
    private $wrapLength = 40;

    /**
     * Renders a table for upgrade wizards
     *
     * @param array $upgradeWizardList
     * @param ConsoleOutput $output
     * @param mixed $verbose
     */
    public function render(array $upgradeWizardList, ConsoleOutput $output, $verbose = false)
    {
        if (empty($upgradeWizardList)) {
            $output->outputLine('<info>None</info>');

            return;
        }
        $tableHeader = ['Identifier', 'Title'];
        if ($verbose) {
            $tableHeader = ['Identifier', 'Description'];
        }

        $tableRows = [];
        $this->calculateColumnSize($upgradeWizardList);
        foreach ($upgradeWizardList as $identifier => $info) {
            if ($info['explanation'] === 'rowUpdater') {
                continue;
            }
            $rowUpdaterInfo = '';
            $identifier = '<code>' . $identifier . '</code>';
            if ($info['className'] === DatabaseRowsUpdateWizard::class) {
                $rowUpdaterInfo = $this->extractRowUpdatersInfo($verbose, $upgradeWizardList);
            }
            $confirmableHint = $info['confirmable'] ? ' ✔' : '';
            $row = [
                $identifier . $confirmableHint,
                wordwrap($info['title'] . $rowUpdaterInfo, $this->wrapLength),
            ];
            if ($verbose) {
                $description = trim($rowUpdaterInfo ?: $info['explanation']);
                if ($info['confirmable']) {
                    $description .= chr(10) . chr(10) . '<info>Confirmation: </info>' . chr(10);
                    $description .= $info['confirmation']['message'];
                }
                $row = [
                    $identifier . $confirmableHint,
                    wordwrap($description, $this->wrapLength),
                ];
            }
            $tableRows[] = $row;
            $tableRows[] = new TableSeparator();
        }
        array_pop($tableRows);

        $output->outputTable($tableRows, $tableHeader);

        $output->outputLine('Legend:');
        $output->outputLine('✔ Wizard is confirmable');
    }

    private function extractRowUpdatersInfo(bool $verbose, array $upgradeWizardList): string
    {
        $rowUpdaters = array_filter(
            $upgradeWizardList,
            function ($wizardInfo) {
                return $wizardInfo['explanation'] === 'rowUpdater';
            }
        );
        if (empty($rowUpdaters)) {
            return '';
        }
        $rowUpdaterInfo = '';
        if (!$verbose) {
            $rowUpdaterInfo = chr(10) . chr(10);
        }
        $rowUpdaterInfo .= 'Row Updaters:' . chr(10) . chr(10);
        foreach ($rowUpdaters as $rowUpdater) {
            $rowUpdaterInfo .= '<code>' . $rowUpdater['className'] . '</code>' . chr(10);
            if ($verbose) {
                $rowUpdaterInfo .= wordwrap($rowUpdater['title'], $this->wrapLength) . chr(10) . chr(10);
            }
        }

        return $rowUpdaterInfo;
    }

    private function calculateColumnSize(array $wizards): void
    {
        $length = $this->wrapLength;
        $rowUpdaterLength = 0;
        foreach ($wizards as $identifier => $info) {
            if ($info['explanation'] === 'rowUpdater') {
                $rowUpdaterLength = strlen($identifier) > $rowUpdaterLength ? strlen($identifier) : $rowUpdaterLength;
                continue;
            }
            $length = strlen($identifier) > $length ? strlen($identifier) : $length;
        }
        $this->wrapLength = max($this->wrapLength, (new Terminal())->getWidth() - $length - 9, $rowUpdaterLength);
    }
}
