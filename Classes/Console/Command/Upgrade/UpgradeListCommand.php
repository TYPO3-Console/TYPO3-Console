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
use Helhum\Typo3Console\Install\Upgrade\UpgradeWizardListRenderer;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeListCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('List upgrade wizards');
        $this->addOption(
            'all',
            'a',
            InputOption::VALUE_NONE,
            'If set, all wizards will be listed, even the once marked as ready or done'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $upgradeHandling = new UpgradeHandling();
        if (!$upgradeHandling->isUpgradePrepared()) {
            $output->writeln('<error>Preparation incomplete. Please run upgrade:prepare before running this command.</error>');

            return 1;
        }

        $all = $input->getOption('all');
        $wizards = $upgradeHandling->listWizards(true);

        $listRenderer = new UpgradeWizardListRenderer();
        // @deprecated usage of ConsoleOutput will be removed with 6.0
        $consoleOutput = new ConsoleOutput($output, $input);

        $output->writeln('<comment>Wizards scheduled for execution:</comment>');
        $listRenderer->render($wizards['scheduled'], $consoleOutput, $output->isVerbose());

        if ($all) {
            $output->writeln(PHP_EOL . '<comment>Wizards marked as done:</comment>');
            $listRenderer->render($wizards['done'], $consoleOutput, $output->isVerbose());
        }

        return 0;
    }
}
