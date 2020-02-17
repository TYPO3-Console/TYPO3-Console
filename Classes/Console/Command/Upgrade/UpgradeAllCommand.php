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
use Helhum\Typo3Console\Install\Upgrade\UpgradeWizardResultRenderer;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeAllCommand extends AbstractConvertedCommand
{
    protected function configure()
    {
        $this->setDescription('Execute all upgrade wizards that are scheduled for execution');
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
                'arguments',
                'a',
                InputOption::VALUE_REQUIRED,
                'Arguments for the wizard prefixed with the identifier, e.g. <code>compatibility7Extension[install]=0</code>; multiple arguments separated with comma',
                []
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
        // @deprecated, should be changed to StyleInterface
        $consoleOutput = new ConsoleOutput($output, $input);
        if (!$this->ensureExtensionCompatibility($upgradeHandling, $output)) {
            return 1;
        }

        $arguments = $input->getOption('arguments');
        $arguments = is_array($arguments) ? $arguments : explode(',', $arguments);
        $verbose = $output->isVerbose();

        $output->writeln(PHP_EOL . '<i>Initiating TYPO3 upgrade</i>' . PHP_EOL);

        $messages = [];
        $results = $upgradeHandling->executeAll($arguments, $consoleOutput, $messages);

        $output->writeln(sprintf(PHP_EOL . PHP_EOL . '<i>Successfully upgraded TYPO3 to version %s</i>', TYPO3_version));

        if ($verbose) {
            $output->writeln('');
            $output->writeln('<comment>Upgrade report:</comment>');
            (new UpgradeWizardResultRenderer())->render($results, $consoleOutput);
        }

        $output->writeln('');
        foreach ($messages as $message) {
            $output->writeln($message);
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
