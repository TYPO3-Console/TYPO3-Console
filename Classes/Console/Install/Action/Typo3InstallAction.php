<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Install\Action;

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

use Helhum\Typo3Console\Install\CliMessageRenderer;
use Helhum\Typo3Console\Install\InstallStepResponse;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;

class Typo3InstallAction implements InstallActionInterface
{
    /**
     * @var CommandDispatcher
     */
    private $commandDispatcher;

    /**
     * @var ConsoleOutput
     */
    private $output;

    public function setOutput(ConsoleOutput $output)
    {
        $this->output = $output;
    }

    public function setCommandDispatcher(CommandDispatcher $commandDispatcher)
    {
        $this->commandDispatcher = $commandDispatcher;
    }

    public function shouldExecute(array $actionDefinition, array $options = []): bool
    {
        $actionName = $options['actionName'];
        if (!$this->executeActionWithArguments('actionNeedsExecution', [$actionName])->actionNeedsExecution()) {
            return false;
        }

        return true;
    }

    public function execute(array $actionDefinition, array $options = []): bool
    {
        $actionName = $options['actionName'];
        $argumentDefinitions = $actionDefinition['arguments'] ?? [];
        $interactiveArguments = new InteractiveActionArguments($this->output);
        $arguments = $this->translateActionArgumentsToCommandArguments(
            $interactiveArguments->populate($argumentDefinitions, $options),
            $argumentDefinitions
        );
        $response = $this->executeActionWithArguments($actionName, $arguments);

        if ($this->executeActionWithArguments('actionNeedsExecution', [$actionName])->actionNeedsExecution()) {
            // @deprecated Can be removed once TYPO3 8.7 support is removed. Then it will be safe to call the action only once
            // and just assume it completed
            $response = $this->executeActionWithArguments($actionName, $arguments);
        }

        $messages = $response->getMessages();
        $messageRenderer = new CliMessageRenderer($this->output);
        $messageRenderer->render($messages);

        return empty($messages);
    }

    private function translateActionArgumentsToCommandArguments(array $actionArguments, $argumentDefinitions): array
    {
        $commandArguments = [];
        foreach ($actionArguments as $name => $value) {
            $argumentDefinition = $argumentDefinitions[$name];
            if ($argumentDefinition['type'] === 'bool') {
                if ((bool)$value) {
                    $commandArguments[] = $argumentDefinition['option'];
                }
            } else {
                $commandArguments[] = $argumentDefinition['option'];
                $commandArguments[] = $value;
            }
        }

        return $commandArguments;
    }

    /**
     * Executes the given action and returns their response messages
     *
     * @param string $actionName Name of the install step
     * @param array $arguments Arguments for the install step
     * @param array $options Options for the install step
     * @return InstallStepResponse
     */
    private function executeActionWithArguments($actionName, array $arguments = [], array $options = [])
    {
        $actionName = strtolower($actionName);
        // Arguments must come first then options, to avoid argument values to be passed to boolean flags
        $arguments = array_merge($arguments, $options);
        $actionResult = $this->commandDispatcher->executeCommand('install:' . $actionName, $arguments);
        $response = @unserialize($actionResult);
        if ($response === false && $actionName === 'defaultconfiguration') {
            // This action terminates with exit, (trying to initiate a HTTP redirect)
            // Therefore we gracefully create a valid response here
            $response = new InstallStepResponse(false, []);
        }

        return $response;
    }
}
