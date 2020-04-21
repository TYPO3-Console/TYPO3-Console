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

use Helhum\Typo3Console\Install\Upgrade\UpgradeHandling;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradePrepareCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Executes preparational upgrade steps and checks basic extension compatibility');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $upgradeHandling = new UpgradeHandling();
        if ($upgradeHandling->isUpgradePrepared()) {
            $output->writeln('<info>Preparation has been done before, repeating preparation and checking extensions.</info>');
        }
        if (!$this->prepareUpgrade($upgradeHandling, $output)) {
            return 1;
        }

        return 0;
    }

    private function prepareUpgrade(UpgradeHandling $upgradeHandling, OutputInterface $output): bool
    {
        $messages = $upgradeHandling->prepareUpgrade();
        if (!empty($messages)) {
            $output->writeln('<error>Incompatible extensions found! Please resolve errors before running upgrade:run.</error>');

            foreach ($messages as $message) {
                $output->writeln($message);
            }

            return false;
        }
        $output->writeln('<success>Upgrade preparations successfully executed.</success>');

        return true;
    }
}
