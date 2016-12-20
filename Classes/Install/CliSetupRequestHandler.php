<?php
namespace Helhum\Typo3Console\Install;

/*
 * This file is part of the TYPO3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Helhum\Typo3Console\Install\Status\RedirectStatus;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition;
use TYPO3\CMS\Extbase\Mvc\Controller\Argument;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException;
use TYPO3\CMS\Install\Controller\Action\ActionInterface;
use TYPO3\CMS\Install\Controller\Action\Step\StepInterface;
use TYPO3\CMS\Install\Controller\Exception\RedirectException;
use TYPO3\CMS\Install\Status\StatusInterface;
use TYPO3\CMS\Install\Status\WarningStatus;

/**
 * This class acts as facade for the install tool step actions.
 * It glues together the execution of these actions with the user interaction on the command line
 */
class CliSetupRequestHandler
{
    const INSTALL_COMMAND_CONTROLLER_CLASS = \Helhum\Typo3Console\Command\InstallCommandController::class;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager;

    /**
     * @var \Helhum\Typo3Console\Mvc\Cli\CommandManager
     * @inject
     */
    protected $commandManager;

    /**
     * @var \Helhum\Typo3Console\Mvc\Cli\CommandDispatcher
     * @inject
     */
    protected $dispatcher;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     * @inject
     */
    protected $reflectionService;

    /**
     * @var array List of necessary installation steps. Order is important!
     */
    protected $installationActions = [
        'environmentAndFolders',
        'databaseConnect',
        'databaseSelect',
        'databaseData',
        'defaultConfiguration',
    ];

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * @var array
     */
    protected $givenRequestArguments = [];

    /**
     * @var bool
     */
    protected $interactiveSetup = true;

    /**
     * Creates a new output object during object creation
     */
    public function initializeObject()
    {
        if ($this->output === null) {
            $this->output = $this->objectManager->get(\Helhum\Typo3Console\Mvc\Cli\ConsoleOutput::class);
        }
    }

    /**
     * @param bool $interactiveSetup
     * @param array $givenRequestArguments
     */
    public function setup($interactiveSetup, array $givenRequestArguments)
    {
        $this->interactiveSetup = $interactiveSetup;
        $this->givenRequestArguments = $givenRequestArguments;

        $firstInstallPath = PATH_site . 'FIRST_INSTALL';
        if (!file_exists($firstInstallPath)) {
            touch($firstInstallPath);
        }

        foreach ($this->installationActions as $actionName) {
            $this->dispatchAction($actionName);
        }
    }

    /**
     * Executes the given action and outputs the result messages
     *
     * @param string $actionName
     * @param array $arguments
     */
    public function executeActionWithArguments($actionName, array $arguments = [])
    {
        $messages = $this->executeAction($this->createActionWithNameAndArguments($actionName, $arguments));
        $this->output->outputLine(serialize($messages));
    }

    /**
     * Checks if the given action needs to be executed or not
     *
     * @param string $actionName
     */
    public function callNeedsExecution($actionName)
    {
        $needsExecution = $this->actionNeedsExecution($this->createActionWithNameAndArguments($actionName));
        $this->output->outputLine((string)$needsExecution);
    }

    /**
     * @param string $actionName
     * @param array $arguments
     * @return StepInterface|ActionInterface
     */
    protected function createActionWithNameAndArguments($actionName, array $arguments = [])
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
     * @return StatusInterface[]
     */
    protected function executeAction(StepInterface $action)
    {
        try {
            $needsExecution = $action->needsExecution();
        } catch (\TYPO3\CMS\Install\Controller\Exception\RedirectException $e) {
            return [new RedirectStatus()];
        }

        if ($needsExecution) {
            return $action->execute();
        } else {
            return [];
        }
    }

    /**
     * @param StepInterface $action
     * @return bool
     */
    protected function actionNeedsExecution(StepInterface $action)
    {
        try {
            $needsExecution = $action->needsExecution();
        } catch (\TYPO3\CMS\Install\Controller\Exception\RedirectException $e) {
            return true;
        }
        return $needsExecution;
    }

    /**
     * @param string $actionName
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\AmbiguousCommandIdentifierException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchCommandException
     * @throws \RuntimeException
     */
    protected function dispatchAction($actionName)
    {
        $this->executeSilentConfigurationUpgradesIfNeeded();

        $arguments = $this->getCommandMethodArguments($actionName . 'Command');
        $command = $this->commandManager->getCommandByIdentifier('install:' . strtolower($actionName));

        $loopCounter = 0;

        do {
            $loopCounter++;
            $this->output->outputLine();
            $this->output->outputLine(sprintf('%s:', $command->getShortDescription()));

            if (!$this->dispatcher->executeCommand('install:' . strtolower($actionName) . 'needsexecution')) {
                $this->output->outputLine('<info>No execution needed, skipped step!</info>');
                return;
            }
            $actionArguments = [];
            /** @var CommandArgumentDefinition $argumentDefinition */
            foreach ($command->getArgumentDefinitions() as $argumentDefinition) {
                $isPasswordArgument = strpos(strtolower($argumentDefinition->getName()), 'password') !== false;
                $argument = $arguments->getArgument($argumentDefinition->getName());
                if (isset($this->givenRequestArguments[$argumentDefinition->getName()])) {
                    $actionArguments[$argumentDefinition->getName()] = $this->givenRequestArguments[$argumentDefinition->getName()];
                } else {
                    if (!$this->interactiveSetup) {
                        if ($this->isArgumentRequired($argument)) {
                            throw new \RuntimeException(sprintf('Argument "%s" is not set, but is required and user interaction has been disabled!', $argument->getName()), 1405273316);
                        } else {
                            continue;
                        }
                    }
                    $argumentValue = null;
                    do {
                        $defaultValue = $argument->getDefaultValue();
                        $isRequired = $this->isArgumentRequired($argument);
                        if ($isPasswordArgument && $isRequired) {
                            $argumentValue = $this->output->askHiddenResponse(
                                sprintf(
                                    '<comment>%s:</comment> ',
                                    $argumentDefinition->getDescription()
                                )
                            );
                        } elseif (is_bool($argument->getValue())) {
                            $argumentValue = (int)$this->output->askConfirmation(
                                sprintf(
                                    '<comment>%s (%s):</comment> ',
                                    $argumentDefinition->getDescription(),
                                    $isRequired ? 'required' : ($defaultValue ? 'Y/n' : 'y/N')
                                ),
                                $defaultValue
                            );
                        } else {
                            $argumentValue = $this->output->ask(
                                sprintf(
                                    '<comment>%s (%s):</comment> ',
                                    $argumentDefinition->getDescription(),
                                    $isRequired ? 'required' : sprintf('default: "%s"', $defaultValue === false ? '0' : $defaultValue)
                                )
                            );
                        }
                    } while ($this->isArgumentRequired($argument) && $argumentValue === null);
                    $actionArguments[$argumentDefinition->getName()] = $argumentValue !== null ? $argumentValue : $argument->getDefaultValue();
                }
            }

            do {
                $messages = @unserialize($this->dispatcher->executeCommand('install:' . strtolower($actionName), $actionArguments));
            } while (!empty($messages[0]) && $messages[0] instanceof RedirectStatus);

            $stillNeedsExecution = (bool)$this->dispatcher->executeCommand('install:' . strtolower($actionName) . 'needsexecution');
            if ($stillNeedsExecution) {
                if (empty($messages)) {
                    $warning = new WarningStatus();
                    $warning->setTitle('Action was not executed successfully!');
                    $warning->setMessage(
                        'Please check if your input values are correct and you have all needed permissions!'
                        . PHP_EOL . '(Could it be that you selected "use existing database", but the chosen database is not empty?)'
                    );
                    $messages = [$warning];
                }
                $this->outputMessages($messages);
            } else {
                $this->output->outputLine('<success>OK</success>');
            }

            if ($loopCounter > 10) {
                throw new \RuntimeException('Tried to dispatch "' . $actionName . '" ' . $loopCounter . ' times.', 1405269518);
            }
        } while ($stillNeedsExecution);
    }

    protected function isArgumentRequired(Argument $argument)
    {
        return $argument->isRequired() || $argument->getDefaultValue() === 'required';
    }

    /**
     * Initializes the arguments array of this controller by creating an empty argument object for each of the
     * method arguments found in the designated command method.
     *
     * @param string $commandMethodName
     * @return Arguments
     * @throws InvalidArgumentTypeException
     */
    protected function getCommandMethodArguments($commandMethodName)
    {
        $arguments = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class);
        $methodParameters = $this->reflectionService->getMethodParameters(self::INSTALL_COMMAND_CONTROLLER_CLASS, $commandMethodName);
        foreach ($methodParameters as $parameterName => $parameterInfo) {
            $dataType = null;
            if (isset($parameterInfo['type'])) {
                $dataType = $parameterInfo['type'];
            } elseif ($parameterInfo['array']) {
                $dataType = 'array';
            }
            if ($dataType === null) {
                throw new InvalidArgumentTypeException(sprintf('The argument type for parameter $%s of method %s->%s() could not be detected.', $parameterName, self::INSTALL_COMMAND_CONTROLLER_CLASS, $commandMethodName), 1306755296);
            }
            $defaultValue = (isset($parameterInfo['defaultValue']) ? $parameterInfo['defaultValue'] : null);
            $arguments->addNewArgument($parameterName, $dataType, ($parameterInfo['optional'] === false), $defaultValue);
        }

        return $arguments;
    }

    /**
     * Call silent upgrade class, redirect to self if configuration was changed.
     *
     * @return void
     * @throws RedirectException
     */
    protected function executeSilentConfigurationUpgradesIfNeeded()
    {
        if (!file_exists(PATH_site . 'typo3conf/LocalConfiguration.php')) {
            return;
        }

        $upgradeService = $this->objectManager->get(
            \TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService::class
        );

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
    protected function reloadConfiguration()
    {
        GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class)->exportConfiguration();
    }

    // Logging and output related stuff
    // TODO: Move to own class

    /**
     * @param StatusInterface[] $messages
     */
    protected function outputMessages(array $messages = [])
    {
        $this->output->outputLine();
        foreach ($messages as $statusMessage) {
            $this->outputStatusMessage($statusMessage);
        }
    }

    /**
     * @param StatusInterface $statusMessage
     */
    protected function outputStatusMessage(StatusInterface $statusMessage)
    {
        $subject = strtoupper($statusMessage->getSeverity()) . ': ' . $statusMessage->getTitle();
        switch ($statusMessage->getSeverity()) {
            case 'error':
            case 'warning':
                $subject = sprintf('<%1$s>' . $subject . '</%1$s>', $statusMessage->getSeverity());
            break;
            default:
        }
        $this->output->outputLine($subject);
        foreach (explode("\n", wordwrap($statusMessage->getMessage())) as $line) {
            $this->output->outputLine(sprintf('<%1$s>' . $line . '</%1$s>', $statusMessage->getSeverity()));
        }
    }
}
