<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Upgrade;

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

trait EnsureExtensionCompatibilityTrait
{
    private function ensureExtensionCompatibility()
    {
        $messages = $this->upgradeHandling->ensureExtensionCompatibility();
        if (!empty($messages)) {
            $this->output->writeln('<error>Incompatible extensions found, aborting.</error>');

            foreach ($messages as $message) {
                $this->output->writeln($message);
            }

            return false;
        }

        return true;
    }
}
