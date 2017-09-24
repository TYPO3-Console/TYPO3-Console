<?php
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

use Helhum\Typo3Console\Core\ConsoleBootstrap;
use Helhum\Typo3Console\Extension\ExtensionCompatibilityCheck;
use Helhum\Typo3Console\Extension\ExtensionConstraintCheck;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;
use Helhum\Typo3Console\Service\Configuration\ConfigurationService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
    private static $wizardsWithArguments = [
        'DbalAndAdodbExtractionUpdate' => [['name' => 'install', 'type' => 'bool', 'default' => '0']],
        'compatibility6Extension' => [['name' => 'install', 'type' => 'bool', 'default' => '0']],
        'compatibility7Extension' => [['name' => 'install', 'type' => 'bool', 'default' => '0']],
        'rtehtmlareaExtension' => [['name' => 'install', 'type' => 'bool', 'default' => '0']],
    ];

    /**
     * @param UpgradeWizardFactory|null $factory
     * @param UpgradeWizardExecutor $executor
     * @param UpgradeWizardList|null $upgradeWizardList
     * @param SilentConfigurationUpgrade|null $silentConfigurationUpgrade
     * @param CommandDispatcher|null $commandDispatcher
     * @param ConfigurationService|null $configurationService
     * @param PackageManager|null $packageManager
     * @param ExtensionConstraintCheck|null $extensionConstraintCheck
     * @param ExtensionCompatibilityCheck|null $extensionCompatibilityCheck
     */
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
        $this->factory = new UpgradeWizardFactory();
        $this->executor = $executor ?: new UpgradeWizardExecutor($this->factory);
        $this->upgradeWizardList = $upgradeWizardList ?: new UpgradeWizardList();
        $this->silentConfigurationUpgrade = $silentConfigurationUpgrade ?: new SilentConfigurationUpgrade();
        $this->commandDispatcher = $commandDispatcher ?: CommandDispatcher::createFromCommandRun();
        $this->configurationService = $configurationService ?: new ConfigurationService();
        $this->packageManager = $packageManager ?: GeneralUtility::makeInstance(PackageManager::class);
        $this->extensionConstraintCheck = $extensionConstraintCheck ?: new ExtensionConstraintCheck();
        $this->extensionCompatibilityCheck = $extensionCompatibilityCheck ?: new ExtensionCompatibilityCheck($this->packageManager, $this->commandDispatcher);
    }

    /**
     * @param string $identifier
     * @param array $rawArguments
     * @param bool $force
     * @return UpgradeWizardResult
     */
    public function executeWizard($identifier, array $rawArguments = [], $force = false)
    {
        return $this->executor->executeWizard($identifier, $rawArguments, $force);
    }

    /**
     * @param array $arguments
     * @param ConsoleOutput|null $consoleOutput
     * @param array &$messages
     * @return array
     */
    public function executeAll(array $arguments, ConsoleOutput $consoleOutput = null, array &$messages = [])
    {
        if ($consoleOutput) {
            $consoleOutput->progressStart(rand(6, 9));
            $consoleOutput->progressAdvance();
        }

        $wizards = $this->executeInSubProcess('listWizards', [], $messages);

        if ($consoleOutput) {
            $consoleOutput->progressStart(count($wizards['scheduled']) + 2);
        }

        $results = [];
        if (!empty($wizards['scheduled'])) {
            foreach ($wizards['scheduled'] as $identifier => $_) {
                if ($consoleOutput) {
                    $consoleOutput->progressAdvance();
                }
                $shortIdentifier = str_replace('TYPO3\\CMS\\Install\\Updates\\', '', $identifier);
                if ($consoleOutput && isset(self::$wizardsWithArguments[$shortIdentifier])
                ) {
                    foreach (self::$wizardsWithArguments[$shortIdentifier] as $argumentDefinition) {
                        $argumentName = $argumentDefinition['name'];
                        $argumentDefault = $argumentDefinition['default'];
                        if ($this->wizardHasArgument($shortIdentifier, $argumentName, $arguments)) {
                            continue;
                        }
                        // In composer mode, skip all install extension wizards!
                        if (ConsoleBootstrap::usesComposerClassLoading()) {
                            $arguments[] = sprintf('%s[%s]=%s', $shortIdentifier, $argumentName, $argumentDefault);
                        } elseif ($argumentDefinition['type'] === 'bool') {
                            $wizard = $this->factory->create($shortIdentifier);
                            $consoleOutput->outputLine(PHP_EOL . PHP_EOL . '<info>' . $wizard->getTitle() . '</info>' . PHP_EOL);
                            $consoleOutput->outputLine(implode(PHP_EOL, array_filter(array_map('trim', explode(chr(10), html_entity_decode(strip_tags($wizard->getUserInput(''))))))));
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
                $results[$identifier] = $this->executeInSubProcess('executeWizard', [$identifier, $arguments], $messages);
            }
        }

        if ($consoleOutput) {
            $consoleOutput->progressAdvance();
        }

        $this->commandDispatcher->executeCommand('database:updateschema');

        if ($consoleOutput) {
            $consoleOutput->progressFinish();
        }

        return $results;
    }

    /**
     * @param string $identifier
     * @param string $argumentName
     * @param array $arguments
     * @return bool
     */
    private function wizardHasArgument($identifier, $argumentName, array $arguments)
    {
        if (isset(self::$wizardsWithArguments[$identifier])) {
            foreach ($arguments as $argument) {
                if (strpos($argument, sprintf('%s[%s]', $identifier, $argumentName)) !== false) {
                    return true;
                }
                if (strpos($argument, '[') === false && strpos($argument, $argumentName) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function listWizards()
    {
        return [
            'scheduled' => $this->upgradeWizardList->listWizards(),
            'done' => $this->upgradeWizardList->listWizards(true),
        ];
    }

    /**
     * @param string $extensionKey
     * @param string $typo3Version
     *
     * @throws \TYPO3\CMS\Core\Package\Exception\UnknownPackageException
     * @return string
     */
    public function matchExtensionConstraints($extensionKey, $typo3Version)
    {
        return $this->extensionConstraintCheck->matchConstraints($this->packageManager->getPackage($extensionKey), $typo3Version);
    }

    /**
     * @param string $typo3Version
     *
     * @return array
     */
    public function matchAllExtensionConstraints($typo3Version)
    {
        return $this->extensionConstraintCheck->matchAllConstraints($this->packageManager->getActivePackages(), $typo3Version);
    }

    /**
     * @param string $extensionKey
     * @param bool $configOnly
     * @return bool
     */
    public function isCompatible($extensionKey, $configOnly = false)
    {
        return $this->extensionCompatibilityCheck->isCompatible($extensionKey, $configOnly);
    }

    /**
     * @return array Array of extension keys of not compatible extensions
     */
    public function findIncompatible()
    {
        return $this->extensionCompatibilityCheck->findIncompatible();
    }

    /**
     * Execute the command in a sub process,
     * but execute some automated migration steps beforehand
     *
     * @param string $command
     * @param array $arguments
     * @param array &$messages
     * @throws FailedSubProcessCommandException
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     * @return mixed
     */
    public function executeInSubProcess($command, array $arguments = [], array &$messages = [])
    {
        $messages = $this->ensureUpgradeIsPossible();
        return @unserialize($this->commandDispatcher->executeCommand('upgrade:subprocess', ['command' => $command, 'arguments' => serialize($arguments)]));
    }

    /**
     * @throws FailedSubProcessCommandException
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     * @return string[]
     */
    private function ensureUpgradeIsPossible()
    {
        $messages = [];
        if (!$this->initialUpgradeDone
            && (
                    !$this->configurationService->hasLocal('EXTCONF/helhum-typo3-console/initialUpgradeDone')
                    || TYPO3_branch !== $this->configurationService->getLocal('EXTCONF/helhum-typo3-console/initialUpgradeDone')
                )
        ) {
            $this->initialUpgradeDone = true;
            $this->configurationService->setLocal('EXTCONF/helhum-typo3-console/initialUpgradeDone', TYPO3_branch, 'string');
            $this->commandDispatcher->executeCommand('install:fixfolderstructure');
            $messages = $this->ensureExtensionCompatibility();
            $this->silentConfigurationUpgrade->executeSilentConfigurationUpgradesIfNeeded();
            // @deprecated if condition can be removed, when TYPO3 7.6 support is removed
            if (class_exists(DatabaseCharsetUpdate::class)) {
                $this->commandDispatcher->executeCommand('upgrade:wizard', ['identifier' => DatabaseCharsetUpdate::class]);
            }
            $this->commandDispatcher->executeCommand('cache:flush');
            $this->commandDispatcher->executeCommand('database:updateschema');
        }
        return $messages;
    }

    /**
     * @return string[]
     */
    private function ensureExtensionCompatibility()
    {
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
