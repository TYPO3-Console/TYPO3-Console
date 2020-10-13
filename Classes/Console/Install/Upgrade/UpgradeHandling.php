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

use Helhum\Typo3Console\Core\Booting\CompatibilityScripts;
use Helhum\Typo3Console\Extension\ExtensionCompatibilityCheck;
use Helhum\Typo3Console\Extension\ExtensionConstraintCheck;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;
use Helhum\Typo3Console\Service\Configuration\ConfigurationService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\AbstractDownloadExtensionUpdate;
use TYPO3\CMS\Install\Updates\DatabaseCharsetUpdate;

/**
 * Executes a single upgrade wizard
 * Holds the information on possible user interactions
 */
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

    /**
     * Wizards that have a user interaction with resulting argument
     *
     * @var array
     */
    private static $extensionWizardArguments = [['name' => 'install', 'type' => 'bool', 'default' => '0']];

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
    }

    public function executeWizard(string $identifier, array $rawArguments = [], bool $force = false): UpgradeWizardResult
    {
        return $this->executor->executeWizard($identifier, $rawArguments, $force);
    }

    public function executeAll(array $arguments, ConsoleOutput $consoleOutput, array &$messages = []): array
    {
        $consoleOutput->progressStart(rand(6, 9));
        $consoleOutput->progressAdvance();

        $wizards = $this->executeInSubProcess('listWizards', []);

        $consoleOutput->progressStart(count($wizards['scheduled']) + 2);

        $results = [];
        if (!empty($wizards['scheduled'])) {
            foreach ($wizards['scheduled'] as $shortIdentifier => $wizardOptions) {
                $consoleOutput->progressAdvance();
                if (is_subclass_of($wizardOptions['className'], AbstractDownloadExtensionUpdate::class)) {
                    foreach (self::$extensionWizardArguments as $argumentDefinition) {
                        $argumentName = $argumentDefinition['name'];
                        $argumentDefault = $argumentDefinition['default'];
                        if ($this->wizardHasArgument($shortIdentifier, $argumentName, $arguments)) {
                            continue;
                        }
                        if (CompatibilityScripts::isComposerMode()) {
                            // In composer mode, skip all install extension wizards!
                            $arguments[] = sprintf('%s[%s]=%s', $shortIdentifier, $argumentName, $argumentDefault);
                            $messages[] = '<warning>Wizard "' . $shortIdentifier . '" was not executed but only marked as executed due to composer mode.</warning>';
                        } elseif ($argumentDefinition['type'] === 'bool') {
                            // We currently only handle one argument type
                            $wizard = $this->factory->create($wizardOptions['className']);
                            $consoleOutput->outputLine(PHP_EOL . PHP_EOL . '<info>' . $wizard->getTitle() . '</info>' . PHP_EOL);
                            if (is_callable([$wizard, 'getUserInput'])) {
                                $consoleOutput->outputLine(implode(PHP_EOL, array_filter(array_map('trim', explode(chr(10), html_entity_decode(strip_tags($wizard->getUserInput(''))))))));
                            }
                            $consoleOutput->outputLine();
                            $arguments[] = sprintf(
                                '%s[%s]=%s',
                                $shortIdentifier,
                                $argumentName,
                                (string)(int)$consoleOutput->askConfirmation('<comment>Install (y/N)</comment> ', $argumentDefault)
                            );
                        }
                    }
                }
                $results[$shortIdentifier] = $this->executeInSubProcess('executeWizard', [$shortIdentifier, $arguments]);
            }
        }

        $consoleOutput->progressAdvance();

        $this->commandDispatcher->executeCommand('database:updateschema');

        $consoleOutput->progressFinish();

        return $results;
    }

    private function wizardHasArgument(string $identifier, string $argumentName, array $arguments): bool
    {
        foreach ($arguments as $argument) {
            if (strpos($argument, sprintf('%s[%s]', $identifier, $argumentName)) !== false) {
                return true;
            }
            if (strpos($argument, '[') === false && strpos($argument, $argumentName) !== false) {
                return true;
            }
        }

        return false;
    }

    public function listWizards(): array
    {
        return [
            'scheduled' => $this->upgradeWizardList->listWizards(),
            'done' => $this->upgradeWizardList->listWizards(true),
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

    /**
     * Execute the command in a sub process,
     * but execute some automated migration steps beforehand
     *
     * @param string $command
     * @param array $arguments
     * @throws FailedSubProcessCommandException
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     * @return mixed
     */
    public function executeInSubProcess($command, array $arguments = [])
    {
        $this->ensureUpgradeIsPossible();

        return @unserialize($this->commandDispatcher->executeCommand('upgrade:subprocess', [$command, serialize($arguments)]));
    }

    /**
     * @throws FailedSubProcessCommandException
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     */
    private function ensureUpgradeIsPossible()
    {
        if ($this->isInitialUpgradeDone()) {
            return;
        }
        $this->initialUpgradeDone = true;
        $this->configurationService->setLocal('EXTCONF/helhum-typo3-console/initialUpgradeDone', TYPO3_branch, 'string');
        $this->commandDispatcher->executeCommand('install:fixfolderstructure');
        $this->silentConfigurationUpgrade->executeSilentConfigurationUpgradesIfNeeded();
        // TODO: Check what we can do here to get TYPO3 9 support for this feature
        if (class_exists(DatabaseCharsetUpdate::class)) {
            $this->commandDispatcher->executeCommand('upgrade:wizard', [DatabaseCharsetUpdate::class]);
        }
        $this->commandDispatcher->executeCommand('cache:flush');
        $this->commandDispatcher->executeCommand('database:updateschema');
    }

    private function isInitialUpgradeDone(): bool
    {
        return $this->initialUpgradeDone
            || (
                $this->configurationService->hasLocal('EXTCONF/helhum-typo3-console/initialUpgradeDone')
                && $this->configurationService->getLocal('EXTCONF/helhum-typo3-console/initialUpgradeDone') === TYPO3_branch
            );
    }

    public function ensureExtensionCompatibility(): array
    {
        if ($this->isInitialUpgradeDone()) {
            return [];
        }
        $messages = [];
        $failedPackageMessages = $this->matchAllExtensionConstraints(TYPO3_version);
        foreach ($failedPackageMessages as $extensionKey => $constraintMessage) {
            $this->packageManager->deactivatePackage($extensionKey);
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
