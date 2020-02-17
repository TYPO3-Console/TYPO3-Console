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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeWizardCommand extends AbstractConvertedCommand
{
    protected function configure()
    {
        $this->setDescription('Execute a single upgrade wizard');
        /** @deprecated Will be removed with 6.0 */
        $this->setDefinition($this->createCompleteInputDefinition());
    }

    /**
     * @deprecated Will be removed with 6.0
     */
    protected function createNativeDefinition(): array
    {
        return [
            new InputArgument(
                'identifier',
                InputArgument::REQUIRED,
                'Identifier of the wizard that should be executed'
            ),
            new InputOption(
                'arguments',
                'a',
                InputOption::VALUE_REQUIRED,
                'Arguments for the wizard prefixed with the identifier, e.g. <code>compatibility7Extension[install]=0</code>',
                []
            ),
            new InputOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force execution, even if the wizard has been marked as done'
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

        $identifier = $input->getArgument('identifier');
        $arguments = $input->getOption('arguments');
        $arguments = is_array($arguments) ? $arguments : explode(',', $arguments);
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
