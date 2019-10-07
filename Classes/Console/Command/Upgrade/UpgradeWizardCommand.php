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
use Helhum\Typo3Console\Install\Upgrade\UpgradeWizardResultRenderer;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeWizardCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Execute a single upgrade wizard');
        $this->addArgument(
            'identifier',
            InputArgument::REQUIRED,
            'Identifier of the wizard that should be executed'
        );
        $this->addOption(
            'arguments',
            'a',
            InputOption::VALUE_REQUIRED,
            'Arguments for the wizard prefixed with the identifier, e.g. <code>compatibility7Extension[install]=0</code>',
            []
        );
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force execution, even if the wizard has been marked as done'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $upgradeHandling = new UpgradeHandling();
        if (!$this->ensureExtensionCompatibility($upgradeHandling, $output)) {
            return 1;
        }

        $identifier = $input->getArgument('identifier');
        $arguments = $input->getOption('arguments');
        $force = $input->getOption('force');

        $result = $upgradeHandling->executeInSubProcess(
            'executeWizard',
            [$identifier, $arguments, $force]
        );

        // @deprecated usage of ConsoleOutput will be removed with 6.0
        (new UpgradeWizardResultRenderer())->render([$identifier => $result], new ConsoleOutput($output, $input));

        return 0;
    }

    private function ensureExtensionCompatibility(UpgradeHandling $upgradeHandling, OutputInterface $output): bool
    {
        $messages = $upgradeHandling->ensureExtensionCompatibility();
        if (!empty($messages)) {
            $output->writeln('<error>Incompatible extensions found, aborting.</error>');

            foreach ($messages as $message) {
                $output->writeln($message);
            }

            return false;
        }

        return true;
    }
}
