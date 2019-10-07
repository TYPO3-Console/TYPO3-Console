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

use Helhum\Typo3Console\Command\AbstractConvertedCommand;
use Helhum\Typo3Console\Install\Upgrade\UpgradeHandling;
use Helhum\Typo3Console\Install\Upgrade\UpgradeWizardListRenderer;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeListCommand extends AbstractConvertedCommand
{
    protected function configure()
    {
        $this->setDescription('List upgrade wizards');
        /** @deprecated Will be removed with 6.0 */
        $this->setDefinition($this->createCompleteInputDefinition());
    }

    /**
     * @deprecated Will be removed with 6.0
     */
    protected function createNativeDefinition(): array
    {
        return [
            new InputOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'If set, all wizards will be listed, even the once marked as ready or done'
            ),
        ];
    }

    /**
     * @deprecated will be removed with 6.0
     */
    protected function handleDeprecatedArgumentsAndOptions(InputInterface $input, OutputInterface $output)
    {
        // nothing to do here
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $upgradeHandling = new UpgradeHandling();
        if (!$this->ensureExtensionCompatibility($upgradeHandling, $output)) {
            return 1;
        }

        $all = $input->getOption('all');
        $wizards = $upgradeHandling->executeInSubProcess('listWizards');

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
