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

use Helhum\Typo3Console\Extension\ExtensionCompatibilityCheck;
use Helhum\Typo3Console\Extension\ExtensionConstraintCheck;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Service\Configuration\ConfigurationService;
use Symfony\Component\Console\Style\OutputStyle;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
     * @var PackageManager
     */
    private $packageManager;

    /**
     * @var ExtensionConstraintCheck
     */
    private $extensionConstraintCheck;

    /**
     * @var ExtensionCompatibilityCheck
     */
    private $extensionCompatibilityCheck;

    /**
     * Flag for same process
     *
     * @var bool
     */
    private $initialUpgradeDone = false;

    private Typo3Version $typo3Version;

    public function __construct(
        UpgradeWizardFactory $factory = null,
        UpgradeWizardExecutor $executor = null,
        UpgradeWizardList $upgradeWizardList = null,
        SilentConfigurationUpgrade $silentConfigurationUpgrade = null,
        CommandDispatcher $commandDispatcher = null,
        ConfigurationService $configurationService = null,
        PackageManager $packageManager = null,
        ExtensionConstraintCheck $extensionConstraintCheck = null,
        ExtensionCompatibilityCheck $extensionCompatibilityCheck = null
    ) {
        $this->factory = $factory ?: new UpgradeWizardFactory();
        $this->executor = $executor ?: new UpgradeWizardExecutor($this->factory);
        $this->upgradeWizardList = $upgradeWizardList ?: new UpgradeWizardList();
        $this->silentConfigurationUpgrade = $silentConfigurationUpgrade ?: new SilentConfigurationUpgrade();
        $this->commandDispatcher = $commandDispatcher ?: CommandDispatcher::createFromCommandRun();
        $this->configurationService = $configurationService ?: new ConfigurationService();
        $this->packageManager = $packageManager ?: GeneralUtility::makeInstance(PackageManager::class);
        $this->extensionConstraintCheck = $extensionConstraintCheck ?: new ExtensionConstraintCheck();
        $this->extensionCompatibilityCheck = $extensionCompatibilityCheck ?: new ExtensionCompatibilityCheck($this->packageManager, $this->commandDispatcher);
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

    public function matchExtensionConstraints(string $extensionKey, string $typo3Version): string
    {
        return $this->extensionConstraintCheck->matchConstraints($this->packageManager->getPackage($extensionKey), $typo3Version);
    }

    public function matchAllExtensionConstraints(string $typo3Version): array
    {
        return $this->extensionConstraintCheck->matchAllConstraints($this->packageManager->getActivePackages(), $typo3Version);
    }

    public function isCompatible(string $extensionKey, bool $configOnly = false): bool
    {
        return $this->extensionCompatibilityCheck->isCompatible($extensionKey, $configOnly);
    }

    public function findIncompatible(): array
    {
        return $this->extensionCompatibilityCheck->findIncompatible();
    }

    public function prepareUpgrade(): array
    {
        $this->configurationService->setLocal('EXTCONF/helhum-typo3-console/initialUpgradeDone', $this->typo3Version->getBranch(), 'string');
        $this->commandDispatcher->executeCommand('install:fixfolderstructure');
        $this->silentConfigurationUpgrade->executeSilentConfigurationUpgradesIfNeeded();
        $this->commandDispatcher->executeCommand('cache:flush', ['--group', 'system']);
        $this->commandDispatcher->executeCommand('database:updateschema');
        $this->commandDispatcher->executeCommand('cache:flush');

        return $this->checkExtensionCompatibility();
    }

    public function isUpgradePrepared(): bool
    {
        return $this->initialUpgradeDone
            || (
                $this->configurationService->hasLocal('EXTCONF/helhum-typo3-console/initialUpgradeDone')
                && $this->configurationService->getLocal('EXTCONF/helhum-typo3-console/initialUpgradeDone') === $this->typo3Version->getBranch()
            );
    }

    private function checkExtensionCompatibility(): array
    {
        $messages = [];
        $failedPackageMessages = $this->matchAllExtensionConstraints($this->typo3Version->getVersion());
        foreach ($failedPackageMessages as $extensionKey => $constraintMessage) {
            !Environment::isComposerMode() && $this->packageManager->deactivatePackage($extensionKey);
            $messages[] = sprintf('<error>%s</error>', $constraintMessage);
            $messages[] = sprintf('<info>Deactivated extension "%s".</info>', $extensionKey);
        }
        foreach ($this->extensionCompatibilityCheck->findIncompatible() as $extensionKey) {
            $messages[] = sprintf('<error>Extension "%s" seems to be not compatible or broken</error>', $extensionKey);
            $messages[] = sprintf('<info>Deactivated extension "%s".</info>', $extensionKey);
        }

        return $messages;
    }
}
