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

/**
 * Renders a list of upgrade wizards
 */
class UpgradeWizardListRenderer
{
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
        foreach ($upgradeWizardList as $identifier => $info) {
            $row = [
                $identifier,
                wordwrap($info['title'], 40),
            ];
            if ($verbose) {
                $row = [
                    $identifier,
                    wordwrap($info['explanation'], 40),
                ];
            }
            $tableRows[] = $row;
            $tableRows[] = new TableSeparator();
        }
        array_pop($tableRows);

        $output->outputTable($tableRows, $tableHeader);
    }
}
