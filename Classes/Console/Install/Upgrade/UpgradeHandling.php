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

use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Service\Configuration\ConfigurationService;
use Symfony\Component\Console\Style\OutputStyle;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Install\Updates\ConfirmableInterface;

class UpgradeHandling
{
    /**
     * @var UpgradeWizardFactory
     */
    private $factory;

    /**
     * @var UpgradeWizardExecutor
     */
    private $executor;

    /**
     * @var SilentConfigurationUpgrade
     */
    private $silentConfigurationUpgrade;

    /**
     * @var CommandDispatcher
     */
    private $commandDispatcher;

    /**
     * @var UpgradeWizardList
     */
    private $upgradeWizardList;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * Flag for same process
     *
     * @var bool
     */
    private $initialUpgradeDone = false;

    private Typo3Version $typo3Version;

    public function __construct(
        ?UpgradeWizardFactory $factory = null,
        ?UpgradeWizardExecutor $executor = null,
        ?UpgradeWizardList $upgradeWizardList = null,
        ?SilentConfigurationUpgrade $silentConfigurationUpgrade = null,
        ?CommandDispatcher $commandDispatcher = null,
        ?ConfigurationService $configurationService = null,
    ) {
        $this->factory = $factory ?: new UpgradeWizardFactory();
        $this->executor = $executor ?: new UpgradeWizardExecutor($this->factory);
        $this->upgradeWizardList = $upgradeWizardList ?: new UpgradeWizardList();
        $this->silentConfigurationUpgrade = $silentConfigurationUpgrade ?: new SilentConfigurationUpgrade();
        $this->commandDispatcher = $commandDispatcher ?: CommandDispatcher::createFromCommandRun();
        $this->configurationService = $configurationService ?: new ConfigurationService();
        $this->typo3Version = new Typo3Version();
    }

    public function runWizards(OutputStyle $io, array $wizards, array $confirmations, array $denies, bool $force): array
    {
        $results = [];
        foreach ($wizards as $identifier) {
            $results[$identifier] = $this->runWizard($io, $identifier, $confirmations, $denies, $force);
        }

        return $results;
    }

    public function runWizard(OutputStyle $io, string $identifier, array $confirmations, array $denies, bool $force = false): UpgradeWizardResult
    {
        $wizard = $this->factory->create($identifier);
        $identifier = $wizard->getIdentifier();
        $isConfirmed = in_array($identifier, $confirmations, true);
        $isDenied = in_array($identifier, $denies, true);
        $arguments = [];
        if ($isConfirmed || $isDenied) {
            $arguments['confirm'] = $isConfirmed;
        }

        $executeWizard = $this->executor->wizardNeedsExecution($identifier) || $force;
        if ($executeWizard && $wizard instanceof ConfirmableInterface && !$isConfirmed && !$isDenied) {
            $confirmation = $wizard->getConfirmation();
            $io->writeln(sprintf('<comment>%s</comment>', $wizard->getTitle()));
            $question = sprintf(
                '<info>%s</info>' . LF . '%s' . LF . '%s' . LF . '%s' . LF,
                $confirmation->getTitle(),
                $confirmation->getMessage(),
                $confirmation->getConfirm(),
                $confirmation->getDeny()
            );
            $arguments['confirm'] = $io->confirm($question, $confirmation->getDefaultValue());
        }

        return $this->executor->executeWizard($identifier, $arguments, $force);
    }

    public function listWizards(bool $includeRowUpdaters = false): array
    {
        return [
            'scheduled' => $this->upgradeWizardList->listWizards(false, $includeRowUpdaters),
            'done' => $this->upgradeWizardList->listWizards(true, $includeRowUpdaters),
        ];
    }

    public function prepareUpgrade(): array
    {
        $this->commandDispatcher->executeCommand('install:fixfolderstructure');
        $this->silentConfigurationUpgrade->executeSilentConfigurationUpgradesIfNeeded();
        $this->commandDispatcher->executeCommand('cache:flush', ['--group', 'system']);
        $this->commandDispatcher->executeCommand('database:updateschema');
        $this->commandDispatcher->executeCommand('cache:flush');
        $this->configurationService->setLocal('EXTCONF/helhum-typo3-console/initialUpgradeDone', $this->typo3Version->getBranch(), 'string');

        return [];
    }

    public function isUpgradePrepared(): bool
    {
        return $this->initialUpgradeDone
            || (
                $this->configurationService->hasLocal('EXTCONF/helhum-typo3-console/initialUpgradeDone')
                && $this->configurationService->getLocal('EXTCONF/helhum-typo3-console/initialUpgradeDone') === $this->typo3Version->getBranch()
            );
    }
}
