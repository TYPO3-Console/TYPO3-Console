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

use Helhum\Typo3Console\Exception;
use Helhum\Typo3Console\Install\Upgrade\UpgradeHandling;
use Helhum\Typo3Console\Install\Upgrade\UpgradeWizardResultRenderer;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;
use TYPO3\CMS\Install\Updates\DatabaseRowsUpdateWizard;

class UpgradeRunCommand extends Command
{
    private const allWizardsOrConfirmations = 'all';

    /**
     * @var UpgradeHandling
     */
    private $upgradeHandling;

    protected function configure()
    {
        $this->setDescription('Run a single upgrade wizard, or all wizards that are scheduled for execution');
        $this->setHelp(
            <<<'EOH'
Runs upgrade wizards.

If "all" is specified as wizard identifier, all wizards that are scheduled are executed.
When no identifier is specified a select UI is presented to select a wizard out of all scheduled ones.

<b>Examples:</b>

  <code>%command.full_name% all</code>

  <code>%command.full_name% all --confirm all</code>

  <code>%command.full_name% argon2iPasswordHashes --confirm all</code>

  <code>%command.full_name% all --confirm all --deny typo3DbLegacyExtension --deny funcExtension</code>

  <code>%command.full_name% all --deny all</code>

  <code>%command.full_name% all --no-interaction --deny all --confirm argon2iPasswordHashes</code>

EOH
        );
        $this->addArgument(
            'wizardIdentifiers',
            InputArgument::REQUIRED | InputArgument::IS_ARRAY,
            'One or more wizard identifiers to run'
        );
        $this->addOption(
            'confirm',
            'y',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Identifier of the wizard, that should be confirmed. Keyword "all" confirms all wizards.'
        );
        $this->addOption(
            'deny',
            'd',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Identifier of the wizard, that should be denied. Keyword "all" denies all wizards. Deny takes precedence except when "all" is specified.'
        );
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force a single wizard to run, despite being marked as executed before. Has no effect on "all"'
        );
        $this->addOption(
            'force-row-updater',
            '',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Identifier of the row updater to be forced to run. Has only effect on "databaseRowsUpdateWizard"'
        );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->upgradeHandling = new UpgradeHandling();
        if (empty($input->getArgument('wizardIdentifiers'))) {
            $scheduledWizards = $this->upgradeHandling->listWizards()['scheduled'];
            if (empty($scheduledWizards)) {
                $input->setArgument('wizardIdentifiers', [self::allWizardsOrConfirmations]);

                return;
            }
            $wizards = [];
            foreach ($scheduledWizards as $identifier => $options) {
                $wizards[$identifier] = $options['title'];
            }
            ksort($wizards);
            $wizards = [self::allWizardsOrConfirmations => 'All scheduled wizards'] + $wizards;
            $io = new SymfonyStyle($input, $output);
            $chosenWizards = $io->askQuestion(
                (new ChoiceQuestion(
                    'Select wizard(s) to run',
                    $wizards,
                    self::allWizardsOrConfirmations
                ))->setMultiselect(true)
            );
            $input->setArgument('wizardIdentifiers', $chosenWizards);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->upgradeHandling = $this->upgradeHandling ?? new UpgradeHandling();
        if (!$this->upgradeHandling->isUpgradePrepared()) {
            $output->writeln('<error>Preparation incomplete. Please run upgrade:prepare before running this command.</error>');

            return 1;
        }
        [$wizardsToExecute, $confirmations, $denies, $force] = $this->unpackArguments($input);
        $io = new SymfonyStyle($input, $output);

        if (empty($wizardsToExecute)) {
            $io->writeln('<success>All wizards done. Nothing to do.</success>');

            return 0;
        }

        $results = $this->upgradeHandling->runWizards($io, $wizardsToExecute, $confirmations, $denies, $force);
        (new UpgradeWizardResultRenderer())->render($results, new ConsoleOutput($output, $input));

        return 0;
    }

    private function unpackArguments(InputInterface $input): array
    {
        $wizardsToExecute = $input->getArgument('wizardIdentifiers');
        if (\in_array(self::allWizardsOrConfirmations, $wizardsToExecute, true)) {
            $wizardsToExecute = [self::allWizardsOrConfirmations];
        }
        $runAllWizards = false;
        if (count($wizardsToExecute) === 1 && $wizardsToExecute[0] === self::allWizardsOrConfirmations) {
            $runAllWizards = true;
            $wizardsToExecute = array_keys($this->upgradeHandling->listWizards()['scheduled']);
        }
        $forceRowUpdaters = $input->getOption('force-row-updater');
        if (!empty($forceRowUpdaters) && !$runAllWizards && !$this->hasRowUpdaterWizards($wizardsToExecute)) {
            // Reset row updaters when DatabaseRowsUpdateWizard is not selected
            $forceRowUpdaters = [];
        }
        if (!empty($forceRowUpdaters)) {
            $upgradeWizardService = GeneralUtility::makeInstance(UpgradeWizardsService::class);
            foreach ($forceRowUpdaters as $rowUpdater) {
                try {
                    $upgradeWizardService->markWizardUndone($rowUpdater);
                } catch (\Throwable $e) {
                    throw new Exception(sprintf('Invalid row updater identifier "%s" given', $rowUpdater), 1587931548);
                }
            }
        }
        $confirmations = $input->getOption('confirm');
        $denies = $input->getOption('deny');
        $force = $input->getOption('force') && !$runAllWizards;
        if ($allowAll = \in_array(self::allWizardsOrConfirmations, $confirmations, true)) {
            $confirmations = $wizardsToExecute;
        }
        if ($denyAll = \in_array(self::allWizardsOrConfirmations, $denies, true)) {
            $denies = $wizardsToExecute;
        }

        if ($denyAll && !$allowAll) {
            // Filter denies, that are present in confirmations
            $denies = array_diff($denies, array_intersect($denies, $confirmations));
        } else {
            // Default: Filter confirmations, that are present in denies
            $confirmations = array_diff($confirmations, array_intersect($confirmations, $denies));
        }

        return [$wizardsToExecute, $confirmations, $denies, $force];
    }

    private function hasRowUpdaterWizards(array $wizardsToExecute): bool
    {
        $allWizards = array_merge($this->upgradeHandling->listWizards()['done'], $this->upgradeHandling->listWizards()['scheduled']);
        foreach ($wizardsToExecute as $identifier) {
            if (isset($allWizards[$identifier]) && $allWizards[$identifier]['wizard'] instanceof DatabaseRowsUpdateWizard) {
                return true;
            }
        }

        return false;
    }
}
