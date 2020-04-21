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

/**
 * Renders results of executed upgrade wizards
 */
class UpgradeWizardResultRenderer
{
    /**
     * Renders a table for upgrade wizards
     *
     * @param UpgradeWizardResult[] $upgradeWizardResults
     * @param ConsoleOutput $output
     */
    public function render(array $upgradeWizardResults, ConsoleOutput $output)
    {
        if (empty($upgradeWizardResults)) {
            return;
        }
        foreach ($upgradeWizardResults as $identifier => $result) {
            if (!empty($messages = array_filter($result->getMessages()))) {
                $output->outputLine();
                $this->printMessages($messages, 'Wizard ' . $identifier, $output);
            }
        }
    }

    private function printMessages(array $messages, string $title, ConsoleOutput $output)
    {
        $output->outputLine('<info>%s:</info>', [$title]);
        foreach ($messages as $message) {
            $output->outputLine($message);
        }
    }
}
