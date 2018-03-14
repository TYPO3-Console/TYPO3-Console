<?php
declare(strict_types=1);
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

use Helhum\Typo3Console\Install\Upgrade\SilentConfigurationUpgrade;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Install\Controller\InstallerController;

/**
 * This class is responsible for properly creating install tool step actions
 * and executing them if needed
 */
class InstallStepActionExecutor
{
    /**
     * @var SilentConfigurationUpgrade
     */
    private $silentConfigurationUpgrade;

    /**
     * @var InstallerController
     */
    private $installerController;
    /**
     * @var callable
     */
    private $requestFactory;

    /**
     * @param SilentConfigurationUpgrade $silentConfigurationUpgrade
     */
    public function __construct(SilentConfigurationUpgrade $silentConfigurationUpgrade, InstallerController $installerController, callable $requestFactory = null)
    {
        $this->silentConfigurationUpgrade = $silentConfigurationUpgrade;
        $this->installerController = $installerController;
        $this->requestFactory = $requestFactory ?: function (array $arguments) {
            return (new ServerRequest())->withParsedBody(
                [
                    'install' => [
                        'values' => $arguments,
                    ],
                ]
            );
        };
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
        $actionMethod = 'execute' . ucfirst($actionName) . 'Action';
        $checkMethod = 'check' . ucfirst($actionName) . 'Action';
        $messages = [];
        $needsExecution = file_exists(PATH_site . 'FIRST_INSTALL');
        if (is_callable([$this->installerController, $checkMethod])) {
            $needsExecution = !\json_decode((string)$this->installerController->$checkMethod()->getBody(), true)['success'];
        }
        if ($needsExecution && !$dryRun) {
            $request = ($this->requestFactory)($arguments);
            try {
                $response = \json_decode((string)$this->installerController->$actionMethod($request)->getBody(), true);
                if (!$response['success']) {
                    $messages = $response['status'];
                }
            } catch (\Throwable $e) {
                $messages = [
                    [
                        'severity' => AbstractMessage::ERROR,
                        'message' => $e->getMessage(),
                    ],
                ];
            }
            $this->silentConfigurationUpgrade->executeSilentConfigurationUpgradesIfNeeded();
            $needsExecution = false;
        }

        return new InstallStepResponse($needsExecution, $messages);
    }
}
