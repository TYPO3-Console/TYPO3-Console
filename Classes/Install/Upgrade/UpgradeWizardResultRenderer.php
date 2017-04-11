<?php
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

/**
 * Renders results of executed upgrade wizards
 */
class UpgradeWizardResultRenderer
{
    /**
     * Renders a table for upgrade wizards
     *
     * @param UpgradeWizardResult[] $upgradeWizardResult
     * @param ConsoleOutput $output
     */
    public function render(array $upgradeWizardResult, ConsoleOutput $output)
    {
        if (empty($upgradeWizardResult)) {
            return;
        }
        foreach ($upgradeWizardResult as $identifier => $result) {
            $identifier = str_replace('TYPO3\\CMS\\Install\\Updates\\', '', $identifier);
            $output->outputLine();
            if (!$result->hasPerformed()) {
                $output->outputLine('<warning>Skipped upgrade wizard "%s" because it was not scheduled for execution or marked as done.</warning>', [$identifier]);
            } else {
                $output->outputLine('<em>Successfully executed upgrade wizard "%s".</em>', [$identifier]);
                if (!empty($messages = array_filter($result->getMessages()))) {
                    $output->outputLine('<info>Messages:</info>');
                    foreach ($messages as $message) {
                        $output->outputLine(html_entity_decode(strip_tags($message)));
                    }
                }
                if (!empty($queries = array_filter($result->getSqlQueries()))) {
                    $output->outputLine('<info>SQL Queries executed:</info>');
                    foreach ($queries as $query) {
                        $output->outputLine(html_entity_decode(($query)));
                    }
                }
            }
        }
    }
}
