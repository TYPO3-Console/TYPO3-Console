<?php
namespace Helhum\Typo3Console\Install;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Install\Controller\Action\ActionInterface;
use TYPO3\CMS\Install\Controller\Action\Step\StepInterface;
use TYPO3\CMS\Install\Controller\Exception\RedirectException;
use TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService;

/**
 * This class is responsible for properly creating install tool step actions
 * and executing them if needed
 */
class InstallStepActionExecutor
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        // @deprecated Object Manager can be removed, once TYPO3 7.6 support is removed
        $this->objectManager = $objectManager;
    }

    /**
     * Executes the given action and returns their response messages
     *
     * @param string $actionName Name of the install step
     * @param array $arguments Arguments for the install step
     * @param bool $dryRun If true, do not execute the action, but only check if execution is necessary
     * @return InstallStepResponse
     */
    public function executeActionWithArguments($actionName, array $arguments = [], $dryRun = false)
    {
        return $this->executeAction($this->createActionWithNameAndArguments($actionName, $arguments), $dryRun);
    }

    /**
     * @param string $actionName
     * @param array $arguments
     * @return StepInterface|ActionInterface
     */
    private function createActionWithNameAndArguments($actionName, array $arguments = [])
    {
        $classPrefix = 'TYPO3\\CMS\\Install\\Controller\\Action\\Step\\';
        $className = $classPrefix . ucfirst($actionName);

        /** @var StepInterface|ActionInterface $action */
        $action = $this->objectManager->get($className);
        $action->setController('step');
        $action->setAction($actionName);
        $action->setPostValues(['values' => $arguments]);

        return $action;
    }

    /**
     * @param StepInterface $action
     * @param bool $dryRun
     * @return InstallStepResponse
     */
    private function executeAction(StepInterface $action, $dryRun = false)
    {
        $messages = [];
        try {
            $needsExecution = $action->needsExecution();
        } catch (RedirectException $e) {
            return new InstallStepResponse(true, $messages, true);
        }
        if ($needsExecution && !$dryRun) {
            $messages = $action->execute();
            $this->executeSilentConfigurationUpgradesIfNeeded();
            $needsExecution = false;
        }
        return new InstallStepResponse($needsExecution, $messages);
    }

    /**
     * Call silent upgrade class, redirect to self if configuration was changed.
     *
     * @throws RedirectException
     * @return void
     */
    private function executeSilentConfigurationUpgradesIfNeeded()
    {
        if (!file_exists(GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class)->getLocalConfigurationFileLocation())) {
            return;
        }
        $upgradeService = $this->objectManager->get(SilentConfigurationUpgradeService::class);
        $count = 0;
        do {
            try {
                $count++;
                $upgradeService->execute();
                $redirect = false;
            } catch (RedirectException $e) {
                $redirect = true;
                $this->reloadConfiguration();
                if ($count > 20) {
                    throw $e;
                }
            }
        } while ($redirect === true);
    }

    /**
     * Fetch the new configuration and expose it to the global array
     */
    private function reloadConfiguration()
    {
        GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class)->exportConfiguration();
    }
}
