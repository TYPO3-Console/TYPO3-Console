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

use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\RepeatableInterface;

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
     * @var UpgradeWizardsService
     */
    private $upgradeWizardsService;

    public function __construct(UpgradeWizardFactory $factory = null, UpgradeWizardsService $upgradeWizardsService = null)
    {
        $this->factory = $factory ?? new UpgradeWizardFactory();
        $this->upgradeWizardsService = $upgradeWizardsService ?? GeneralUtility::makeInstance(UpgradeWizardsService::class);
    }

    public function executeWizard(string $identifier, array $arguments = [], bool $force = false): UpgradeWizardResult
    {
        $upgradeWizard = $this->factory->create($identifier);
        $identifier = $upgradeWizard->getIdentifier();
        $messages = [];
        $hasPerformed = $userHasDecided = $requiresConfirmation = $succeeded = false;
        $userWantsExecution = true;
        $output = new BufferedOutput();
        $isWizardDone = $this->upgradeWizardsService->isWizardDone($identifier);

        if ($upgradeWizard instanceof ConfirmableInterface) {
            $userHasDecided = isset($arguments['confirm']);
            $requiresConfirmation = $upgradeWizard->getConfirmation()->isRequired();
            $userWantsExecution = !empty($arguments['confirm']);
        }
        if ($upgradeWizard instanceof ChattyInterface) {
            $upgradeWizard->setOutput($output);
        }

        $checkForUpdateNecessary = $userWantsExecution && (!$isWizardDone || $force);
        if ($checkForUpdateNecessary && $upgradeWizard->updateNecessary()) {
            $succeeded = $upgradeWizard->executeUpdate();
            $hasPerformed = true;
        }
        $messages[] = $output->fetch();
        if ($succeeded) {
            $messages[] = sprintf('<em>Successfully executed upgrade wizard "%s".</em>', $identifier);
        }
        if ($hasPerformed && !$succeeded) {
            $messages[] = sprintf('<error>Upgrade wizard "%s" had errors during execution.</error>', $identifier);
        }
        if ($hasPerformed && $force && $isWizardDone) {
            $messages[] = sprintf('<info>Upgrade wizard "%s" was executed (forced).</info>', $identifier);
        }
        if (!$hasPerformed && !$force && $isWizardDone) {
            $messages[] = sprintf('<info>Upgrade wizard "%s" was skipped because it is marked as done.</info>', $identifier);
        }
        if (!$hasPerformed && $checkForUpdateNecessary) {
            $messages[] = sprintf('<info>Upgrade wizard "%s" was skipped because no operation is needed.</info>', $identifier);
        }
        if ($userHasDecided && !$hasPerformed) {
            if ($requiresConfirmation && !$isWizardDone) {
                $messages[] = sprintf('<error>Skipped wizard "%s" but it needs confirmation!</error>', $identifier);
            } else {
                $messages[] = sprintf('<info>Skipped wizard "%s" and marked as executed.</info>', $identifier);
            }
        }
        if (!$isWizardDone && ($succeeded || ($checkForUpdateNecessary && !$hasPerformed)) && !$upgradeWizard instanceof RepeatableInterface) {
            $this->upgradeWizardsService->markWizardAsDone($identifier);
        }
        if (!$isWizardDone && $userHasDecided && !$hasPerformed && !$requiresConfirmation) {
            $this->upgradeWizardsService->markWizardAsDone($identifier);
        }

        return new UpgradeWizardResult($hasPerformed, $messages, $succeeded);
    }

    public function wizardNeedsExecution(string $identifier): bool
    {
        $upgradeWizard = $this->factory->create($identifier);

        return $upgradeWizard->updateNecessary();
    }
}
