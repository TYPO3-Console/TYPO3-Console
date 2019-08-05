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
use TYPO3\CMS\Install\Updates\RepeatableInterface;
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

    /**
     * @var Registry
     */
    private $registry;

    public function __construct(UpgradeWizardFactory $factory = null, Registry $registry = null)
    {
        $this->factory = $factory ?? new UpgradeWizardFactory();
        $this->registry = $registry ?? GeneralUtility::makeInstance(Registry::class);
    }

    public function executeWizard(string $identifier, array $rawArguments = [], bool $force = false): UpgradeWizardResult
    {
        $upgradeWizard = $this->factory->create($identifier);
        $dbQueries = [];
        $messages = [];
        // Create new buffered output to be able to capture it later on
        $output = new BufferedOutput();
        if ($upgradeWizard instanceof ChattyInterface) {
            $upgradeWizard->setOutput($output);
        }

        $wizardImplementsInterface = $upgradeWizard instanceof UpgradeWizardInterface && !$upgradeWizard instanceof AbstractUpdate;
        if ($force) {
            if ($wizardImplementsInterface) {
                $this->registry->set('installUpdate', get_class($upgradeWizard), 0);
            } else {
                $closure = \Closure::bind(function () use ($upgradeWizard) {
                    /** @var DummyUpgradeWizard $upgradeWizard here to avoid annoying (and wrong) protected method inspection in PHPStorm */
                    $upgradeWizard->markWizardAsDone(0);
                }, null, $upgradeWizard);
                $closure();
            }
        }

        if (!$wizardImplementsInterface && !$upgradeWizard->shouldRenderWizard()) {
            return new UpgradeWizardResult(false, $dbQueries, $messages);
        }

        if ($wizardImplementsInterface && !$upgradeWizard->updateNecessary()) {
            $messages[] = $output->fetch();

            return new UpgradeWizardResult(false, $dbQueries, $messages);
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

        if ($wizardImplementsInterface) {
            $hasPerformed = $upgradeWizard->executeUpdate();

            if (!$upgradeWizard instanceof RepeatableInterface) {
                $this->registry->set('installUpdate', get_class($upgradeWizard), 1);
            }

            $messages[] = $output->fetch();
        } else {
            $message = '';
            $hasPerformed = $upgradeWizard->performUpdate($dbQueries, $message);
            if ($message !== '') {
                $messages[] = $message;
            }
        }

        return new UpgradeWizardResult($hasPerformed, $dbQueries, $messages);
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
