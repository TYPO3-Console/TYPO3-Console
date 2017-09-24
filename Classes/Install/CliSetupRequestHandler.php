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

use Helhum\Typo3Console\Core\ConsoleBootstrap;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\CommandManager;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;
use TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition;
use TYPO3\CMS\Extbase\Mvc\Controller\Argument;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

/**
 * This class acts as facade for the install tool step actions.
 * It glues together the execution of these actions with the user interaction on the command line
 */
class CliSetupRequestHandler
{
    const INSTALL_COMMAND_CONTROLLER_CLASS = \Helhum\Typo3Console\Command\InstallCommandController::class;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var CommandManager
     */
    protected $commandManager;

    /**
     * @var CommandDispatcher
     */
    private $commandDispatcher;

    /**
     * @var ReflectionService
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
     * @var CliMessageRenderer
     */
    private $messageRenderer;

    /**
     * @var array
     */
    protected $givenRequestArguments = [];

    /**
     * @var bool
     */
    protected $interactiveSetup = true;

    /**
     * CliSetupRequestHandler constructor.
     *
     * @param ObjectManager $objectManager
     * @param CommandManager $commandManager
     * @param ReflectionService $reflectionService
     * @param CommandDispatcher $commandDispatcher
     * @param ConsoleOutput $output
     */
    public function __construct(
        ObjectManager $objectManager,
        CommandManager $commandManager,
        ReflectionService $reflectionService,
        CommandDispatcher $commandDispatcher = null,
        ConsoleOutput $output = null,
        CliMessageRenderer $messageRenderer = null
    ) {
        $this->objectManager = $objectManager;
        $this->commandManager = $commandManager;
        $this->reflectionService = $reflectionService;
        $this->commandDispatcher = $commandDispatcher ?: CommandDispatcher::createFromCommandRun();
        $this->output = $output ?: new ConsoleOutput();
        $this->messageRenderer = $messageRenderer ?: new CliMessageRenderer($this->output);
    }

    /**
     * @param bool $interactiveSetup
     * @param array $givenRequestArguments
     * @param bool $skipExtensionSetup
     */
    public function setup($interactiveSetup, array $givenRequestArguments, $skipExtensionSetup = false)
    {
        $this->interactiveSetup = $interactiveSetup;
        $this->givenRequestArguments = $givenRequestArguments;

        $firstInstallPath = PATH_site . 'FIRST_INSTALL';
        if (!file_exists($firstInstallPath)) {
            touch($firstInstallPath);
        }

        if (!$skipExtensionSetup) {
            // Start with a clean set of packages
            @unlink(PATH_site . 'typo3conf/PackageStates.php');
            $packageStatesArguments = [];
            // Exclude all local extensions in case any are present, to avoid interference with the setup
            foreach (glob(PATH_site . 'typo3conf/ext/*') as $item) {
                $packageStatesArguments['--excluded-extensions'][] = basename($item);
            }
            $this->commandDispatcher->executeCommand('install:generatepackagestates', $packageStatesArguments);
        }

        foreach ($this->installationActions as $actionName) {
            $this->dispatchAction($actionName);
        }
        if (!$skipExtensionSetup) {
            // The TYPO3 installation process does not take care of setting up all extensions properly,
            // so we do it manually here.
            $this->output->outputLine();
            $this->output->outputLine('Set up extensions:');
            try {
                $this->commandDispatcher->executeCommand('install:generatepackagestates');
            } catch (FailedSubProcessCommandException $e) {
                // There are very likely broken extensions or extensions with invalid dependencies
                // Therefore we fall back to TYPO3 standard behaviour and only install default TYPO3 core extensions
                // @deprecated in 4.6, will be removed in 5.0.0
                $packageStatesArguments['--activate-default'] = true;
                $this->commandDispatcher->executeCommand('install:generatepackagestates');
                $this->output->outputLine('<warning>An error occurred while generating PackageStates.php</warning>');
                $this->output->outputLine('<warning>Most likely you have missed correctly specifying depedencies to typo3/cms-* packages</warning>');
                $this->output->outputLine('<warning>The error message was "%s"</warning>', [$e->getPrevious()->getMessage()]);
            }
            $this->commandDispatcher->executeCommand('database:updateschema');
            $this->commandDispatcher->executeCommand('extension:setupactive');
        }
        $this->output->outputLine('<success>OK</success>');
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
        $arguments = $this->getCommandMethodArguments($actionName . 'Command');
        $command = $this->commandManager->getCommandByIdentifier('install:' . strtolower($actionName));
        $loopCounter = 0;
        do {
            $loopCounter++;
            $this->output->outputLine();
            $this->output->outputLine(sprintf('%s:', $command->getShortDescription()));

            if (!$this->checkIfActionNeedsExecution($actionName)->actionNeedsExecution()) {
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
                        }
                        continue;
                    }
                    $argumentValue = null;
                    do {
                        $defaultValue = $argument->getDefaultValue();
                        $isRequired = $this->isArgumentRequired($argument);
                        if ($isPasswordArgument) {
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
            $response = $this->executeActionWithArguments($actionName, $actionArguments);
            if ($this->checkIfActionNeedsExecution($actionName)->actionNeedsExecution()) {
                $response = $this->executeActionWithArguments($actionName, $actionArguments);
            }
            $messages = $response->getMessages();
            if (empty($messages)) {
                $this->output->outputLine('<success>OK</success>');
            } else {
                $this->messageRenderer->render($messages);
            }

            if ($loopCounter > 10) {
                throw new \RuntimeException('Tried to dispatch "' . $actionName . '" ' . $loopCounter . ' times.', 1405269518);
            }
        } while (!empty($messages));
    }

    /**
     * Check if the given action is determined to be executed
     *
     * @param string $actionName Name of the install step
     * @return InstallStepResponse
     */
    private function checkIfActionNeedsExecution($actionName)
    {
        return $this->executeActionWithArguments('actionNeedsExecution', ['actionName' => $actionName]);
    }

    /**
     * Executes the given action and returns their response messages
     *
     * @param string $actionName Name of the install step
     * @param array $arguments Arguments for the install step
     * @return InstallStepResponse
     */
    private function executeActionWithArguments($actionName, array $arguments = [])
    {
        $actionName = strtolower($actionName);
        if (
            $actionName === 'defaultconfiguration'
            && !empty($arguments['siteSetupType'])
            && $arguments['siteSetupType'] === 'dist'
            && ConsoleBootstrap::usesComposerClassLoading()
        ) {
            $errorMessage = new ErrorStatus();
            $errorMessage->setMessage('Site setup type "dist" is not possible when TYPO3 is installed with composer. Use "composer require" to install a distribution of your choice.');
            return new InstallStepResponse(
                true,
                [
                    $errorMessage,
                ]
            );
        }
        $response = @unserialize($this->commandDispatcher->executeCommand('install:' . $actionName, $arguments));
        if ($response === false && $actionName === 'defaultconfiguration') {
            // This action terminates with exit, (trying to initiate a HTTP redirect)
            // Therefore we gracefully create a valid response here
            $response = new InstallStepResponse(false, []);
        }
        return $response;
    }

    /**
     * @param Argument $argument
     * @return bool
     */
    private function isArgumentRequired(Argument $argument)
    {
        return $argument->isRequired() || $argument->getDefaultValue() === 'required';
    }

    /**
     * Initializes the arguments array of this controller by creating an empty argument object for each of the
     * method arguments found in the designated command method.
     *
     * @param string $commandMethodName
     * @throws InvalidArgumentTypeException
     * @return Arguments
     */
    private function getCommandMethodArguments($commandMethodName)
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
}
