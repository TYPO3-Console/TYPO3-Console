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
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Helhum\Typo3Console\Service\Configuration\ConfigurationService;
use TYPO3\CMS\Install\Updates\DatabaseCharsetUpdate;

/**
 * Executes a single upgrade wizard
 * Holds the information on possible user interactions
 */
class UpgradeHandling
{
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
     * @var UpgradeWizardFactory
     */
    private $factory;

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
     */
    public function __construct(
        UpgradeWizardFactory $factory = null,
        UpgradeWizardExecutor $executor = null,
        UpgradeWizardList $upgradeWizardList = null,
        SilentConfigurationUpgrade $silentConfigurationUpgrade = null,
        CommandDispatcher $commandDispatcher = null,
        ConfigurationService $configurationService = null
    ) {
        $this->factory = new UpgradeWizardFactory();
        $this->executor = $executor ?: new UpgradeWizardExecutor($this->factory);
        $this->upgradeWizardList = $upgradeWizardList ?: new UpgradeWizardList();
        $this->silentConfigurationUpgrade = $silentConfigurationUpgrade ?: new SilentConfigurationUpgrade();
        $this->commandDispatcher = $commandDispatcher ?: CommandDispatcher::createFromCommandRun();
        $this->configurationService = $configurationService ?: new ConfigurationService();
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
     * @return array
     */
    public function executeAll(array $arguments, ConsoleOutput $consoleOutput = null)
    {
        if ($consoleOutput) {
            $consoleOutput->progressStart(rand(6, 9));
            $consoleOutput->progressAdvance();
        }

        $wizards = $this->executeInSubProcess('listWizards');

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
                $results[$identifier] = $this->executeInSubProcess('executeWizard', [$identifier, $arguments]);
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
     * Execute the command in a sub process,
     * but execute some automated migration steps beforehand
     *
     * @param string $command
     * @param array $arguments
     * @return mixed
     */
    public function executeInSubProcess($command, array $arguments = [])
    {
        $this->ensureUpgradeIsPossible();
        return @unserialize($this->commandDispatcher->executeCommand('upgrade:subprocess', ['command' => $command, 'arguments' => serialize($arguments)]));
    }

    private function ensureUpgradeIsPossible()
    {
        if (!$this->initialUpgradeDone && !$this->configurationService->hasActive('EXTCONF/helhum-typo3-console/initialUpgradeDone')) {
            $this->initialUpgradeDone = true;
            $this->configurationService->setLocal('EXTCONF/helhum-typo3-console/initialUpgradeDone', true);
            $this->silentConfigurationUpgrade->executeSilentConfigurationUpgradesIfNeeded();
            $this->commandDispatcher->executeCommand('upgrade:wizard', ['identifier' => DatabaseCharsetUpdate::class]);
            $this->commandDispatcher->executeCommand('database:updateschema');
        }
    }
}
