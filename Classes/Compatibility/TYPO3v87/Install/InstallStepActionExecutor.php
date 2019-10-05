<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\TYPO3v87\Install;

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

use Helhum\Typo3Console\Install\InstallStepResponse;
use Helhum\Typo3Console\Install\Upgrade\SilentConfigurationUpgrade;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Install\Controller\Action\ActionInterface;
use TYPO3\CMS\Install\Controller\Action\Step\StepInterface;
use TYPO3\CMS\Install\Controller\Exception\RedirectException;
use TYPO3\CMS\Install\Status\ErrorStatus;

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
     * @var SilentConfigurationUpgrade
     */
    private $silentConfigurationUpgrade;

    /**
     * @param ObjectManager $objectManager
     * @param SilentConfigurationUpgrade $silentConfigurationUpgrade
     */
    public function __construct(SilentConfigurationUpgrade $silentConfigurationUpgrade, ObjectManager $objectManager = null)
    {
        $this->silentConfigurationUpgrade = $silentConfigurationUpgrade;
        $this->objectManager = $objectManager ?? GeneralUtility::makeInstance(ObjectManager::class);
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
     * @throws \TYPO3\CMS\Install\Controller\Exception\RedirectException
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
            try {
                $messages = $action->execute();
            } catch (\Throwable $e) {
                $errorMessage = new ErrorStatus();
                $errorMessage->setMessage($e->getMessage());
                $messages = [
                    $errorMessage,
                ];
            }
            $this->silentConfigurationUpgrade->executeSilentConfigurationUpgradesIfNeeded();
            $needsExecution = false;
        }

        return new InstallStepResponse($needsExecution, $messages);
    }
}
