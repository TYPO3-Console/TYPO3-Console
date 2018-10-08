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

use Helhum\Typo3Console\Tests\Unit\Install\Upgrade\Fixture\DummyUpgradeWizard;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\AbstractUpdate;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Executes a single upgrade wizard
 * Holds the information on possible user interactions
 */
class UpgradeWizardExecutor
{
    /**
     * @var UpgradeWizardFactory
     */
    private $factory;

    public function __construct(UpgradeWizardFactory $factory = null)
    {
        $this->factory = $factory ?: new UpgradeWizardFactory();
    }

    public function executeWizard(string $identifier, array $rawArguments = [], bool $force = false): UpgradeWizardResult
    {
        $upgradeWizard = $this->factory->create($identifier);
        $wizardImplementsInterface = $upgradeWizard instanceof UpgradeWizardInterface && !$upgradeWizard instanceof AbstractUpdate;
        if ($force) {
            if ($wizardImplementsInterface) {
                GeneralUtility::makeInstance(Registry::class)->set('installUpdate', $upgradeWizard->getIdentifier(), 0);
            } else {
                $closure = \Closure::bind(function () use ($upgradeWizard) {
                    /** @var DummyUpgradeWizard $upgradeWizard here to avoid annoying (and wrong) protected method inspection in PHPStorm */
                    $upgradeWizard->markWizardAsDone(0);
                }, null, $upgradeWizard);
                $closure();
            }
        }

        if (!$wizardImplementsInterface && !$upgradeWizard->shouldRenderWizard()) {
            return new UpgradeWizardResult(false);
        }

        if ($wizardImplementsInterface && !$upgradeWizard->updateNecessary()) {
            return new UpgradeWizardResult(false);
        }

        // OMG really?
        @GeneralUtility::_GETset(
            [
                'values' => [
                    $identifier => $this->processRawArguments($identifier, $rawArguments),
                    'TYPO3\\CMS\\Install\\Updates\\' . $identifier => $this->processRawArguments($identifier, $rawArguments),
                ],
            ],
            'install'
        );

        $output = new BufferedOutput();
        if ($upgradeWizard instanceof ChattyInterface) {
            $upgradeWizard->setOutput($output);
        }

        $dbQueries = [];
        $message = '';
        if ($wizardImplementsInterface) {
            $hasPerformed = $upgradeWizard->executeUpdate();
            GeneralUtility::makeInstance(Registry::class)->set('installUpdate', $upgradeWizard->getIdentifier(), 1);
            $message = trim($message . PHP_EOL . $output->fetch());
        } else {
            $hasPerformed = $upgradeWizard->performUpdate($dbQueries, $message);
        }

        return new UpgradeWizardResult($hasPerformed, $dbQueries, [$message]);
    }

    public function wizardNeedsExecution(string $identifier): bool
    {
        $upgradeWizard = $this->factory->create($identifier);

        if ($upgradeWizard instanceof UpgradeWizardInterface && !$upgradeWizard instanceof AbstractUpdate) {
            return $upgradeWizard->updateNecessary();
        }

        return $upgradeWizard->shouldRenderWizard();
    }

    private function processRawArguments(string $identifier, array $rawArguments = [])
    {
        $processedArguments = [];
        foreach ($rawArguments as $argument) {
            parse_str($argument, $processedArgument);
            $processedArguments = array_replace_recursive($processedArguments, $processedArgument);
        }
        $argumentNamespace = str_replace('TYPO3\\CMS\\Install\\Updates\\', '', $identifier);

        return isset($processedArguments[$argumentNamespace]) ? $processedArguments[$argumentNamespace] : $processedArguments;
    }
}
