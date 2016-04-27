<?php
namespace Helhum\Typo3Console\Install;

/*
 * This file is part of the typo3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException;
use TYPO3\CMS\Install\Controller\Action\ActionInterface;
use TYPO3\CMS\Install\Controller\Exception\RedirectException;
use TYPO3\CMS\Install\Status\StatusInterface;

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
    protected $installationActions = array(
        'environmentAndFolders',
        'databaseConnect',
        'databaseSelect',
        'databaseData',
        'defaultConfiguration',
    );

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * @var array
     */
    protected $givenRequestArguments = array();

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

        touch(PATH_site . 'FIRST_INSTALL');
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
    public function executeActionWithArguments($actionName, array $arguments = array())
    {
        // TODO: provide pre- and post-execute signals?
        $messages = $this->executeAction($this->createActionWithNameAndArguments($actionName, $arguments));
        // TODO: ultimately get rid of that!
        if ($actionName === 'databaseData') {
            /** @var DatabaseConnection $db */
            $db = $GLOBALS['TYPO3_DB'];
            $db->exec_INSERTquery('be_users', array('username' => '_cli_lowlevel'));
        }
        $this->output->outputLine(serialize($messages));
    }

    /**
     * @param string $actionName
     * @param array $arguments
     * @return ActionInterface
     */
    protected function createActionWithNameAndArguments($actionName, array $arguments = array())
    {
        $classPrefix = 'TYPO3\\CMS\\Install\\Controller\\Action\\Step\\';
        $className = $classPrefix . ucfirst($actionName);

        /** @var ActionInterface $action */
        $action = $this->objectManager->get($className);
        $action->setController('step');
        $action->setAction($actionName);
        $action->setPostValues(array('values' => $arguments));

        return $action;
    }

    /**
     * @param ActionInterface $action
     * @return bool|string
     */
    protected function executeAction(ActionInterface $action)
    {
        try {
            $needsExecution = $action->needsExecution();
        } catch (\TYPO3\CMS\Install\Controller\Exception\RedirectException $e) {
            return 'REDIRECT';
        }

        if ($needsExecution) {
            return $action->execute();
        } else {
            return array();
        }
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

            $actionArguments = array();
            /** @var \TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition $argumentDefinition */
            foreach ($command->getArgumentDefinitions() as $argumentDefinition) {
                $isPasswordArgument = strpos(strtolower($argumentDefinition->getName()), 'password') !== false;
                $argument = $arguments->getArgument($argumentDefinition->getName());
                if (isset($this->givenRequestArguments[$argumentDefinition->getName()])) {
                    $actionArguments[$argumentDefinition->getName()] = $this->givenRequestArguments[$argumentDefinition->getName()];
                } else {
                    if (!$this->interactiveSetup) {
                        if ($argument->isRequired()) {
                            throw new \RuntimeException(sprintf('Argument "%s" is not set, but is required and user interaction has been disabled!', $argument->getName()), 1405273316);
                        } else {
                            continue;
                        }
                    }
                    $argumentValue = null;
                    do {
                        if ($isPasswordArgument) {
                            $argumentValue = $this->output->askHiddenResponse(
                                sprintf(
                                    '<comment>%s (%s):</comment> ',
                                    $argumentDefinition->getDescription(),
                                    $argument->isRequired() ? 'required' : sprintf('default: "%s"', $argument->getDefaultValue())
                                )
                            );
                        } else {
                            $argumentValue = $this->output->ask(
                                sprintf(
                                    '<comment>%s (%s):</comment> ',
                                    $argumentDefinition->getDescription(),
                                    $argument->isRequired() ? 'required' : sprintf('default: "%s"', $argument->getDefaultValue())
                                )
                            );
                        }
                    } while ($argumentDefinition->isRequired() && $argumentValue === null);
                    $actionArguments[$argumentDefinition->getName()] = $argumentValue !== null ? $argumentValue : $argument->getDefaultValue();
                }
            }

            do {
                $messages = @unserialize($this->dispatcher->executeCommand('install:' . strtolower($actionName), $actionArguments));
            } while ($messages === 'REDIRECT');
            $messages = $messages ?: array();

            $this->outputMessages($messages);

            if ($loopCounter > 10) {
                throw new \RuntimeException('Tried to dispatch "' . $actionName . '" ' . $loopCounter . ' times.', 1405269518);
            }
        } while (!empty($messages));
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
    protected function outputMessages(array $messages = array())
    {
        if (empty($messages)) {
            $this->output->outputLine('OK');
            return;
        }
        $this->output->outputLine();
        foreach ($messages as $statusMessage) {
            $this->outputStatusMessage($statusMessage);
        }
        $this->output->outputLine();
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
