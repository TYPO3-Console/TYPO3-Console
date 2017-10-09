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

use Helhum\Typo3Console\Command\InstallCommandController;
use Helhum\Typo3Console\Mvc\Cli\Command;
use Helhum\Typo3Console\Mvc\Cli\CommandArgumentDefinition;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Symfony\Component\Console\Exception\RuntimeException;
use TYPO3\CMS\Extbase\Mvc\Cli\CommandManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

/**
 * This class acts as facade for the install tool step actions.
 * It glues together the execution of these actions with the user interaction on the command line
 */
class CliSetupRequestHandler
{
    const INSTALL_COMMAND_CONTROLLER_CLASS = \Helhum\Typo3Console\Command\InstallCommandController::class;

    /**
     * @var ObjectManagerInterface
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
     * @param ObjectManagerInterface $objectManager
     * @param CommandManager $commandManager
     * @param ReflectionService $reflectionService
     * @param CommandDispatcher $commandDispatcher
     * @param ConsoleOutput $output
     * @param CliMessageRenderer $messageRenderer
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
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
            $this->commandDispatcher->executeCommand('install:generatepackagestates');
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
     * @throws RuntimeException
     */
    protected function dispatchAction($actionName)
    {
        $command = $this->objectManager->get(Command::class, InstallCommandController::class, $actionName);
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
            foreach ($command->getArgumentDefinitions() as $argumentDefinition) {
                $isPasswordArgument = strpos($argumentDefinition->getOptionName(), 'password') !== false;
                $isRequired = $argumentDefinition->isArgument();
                if (isset($this->givenRequestArguments[$argumentDefinition->getName()])) {
                    $this->setActionArgument($actionArguments, $this->givenRequestArguments[$argumentDefinition->getName()], $argumentDefinition);
                } else {
                    if (!$this->interactiveSetup) {
                        if ($isRequired) {
                            throw new RuntimeException(sprintf('Argument "%s" is not set, but is required and user interaction has been disabled!', $argumentDefinition->getName()), 1405273316);
                        }
                        continue;
                    }
                    $argumentValue = null;
                    do {
                        $defaultValue = $argumentDefinition->getDefaultValue();
                        if ($isPasswordArgument) {
                            $argumentValue = $this->output->askHiddenResponse(
                                sprintf(
                                    '<comment>%s:</comment> ',
                                    $argumentDefinition->getDescription()
                                )
                            );
                        } elseif (!$argumentDefinition->acceptsValue()) {
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
                    } while ($isRequired && $argumentValue === null);
                    $this->setActionArgument($actionArguments, $argumentValue !== null ? $argumentValue : $argumentDefinition->getDefaultValue(), $argumentDefinition);
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
                throw new RuntimeException('Tried to dispatch "' . $actionName . '" ' . $loopCounter . ' times.', 1405269518);
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
        return $this->executeActionWithArguments('actionNeedsExecution', [$actionName]);
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
        $response = @unserialize($this->commandDispatcher->executeCommand('install:' . $actionName, $arguments));
        if ($response === false && $actionName === 'defaultconfiguration') {
            // This action terminates with exit, (trying to initiate a HTTP redirect)
            // Therefore we gracefully create a valid response here
            $response = new InstallStepResponse(false, []);
        }
        return $response;
    }

    private function setActionArgument(&$currentActionArguments, $value, CommandArgumentDefinition $argumentDefinition)
    {
        if ($argumentDefinition->isArgument()) {
            $currentActionArguments[] = $value;
        } else {
            if ($argumentDefinition->acceptsValue()) {
                $currentActionArguments[$argumentDefinition->getDashedName()] = $value;
            } else {
                $value = (bool)$value;
                if ($value) {
                    $currentActionArguments[] = $argumentDefinition->getDashedName();
                }
            }
        }
    }
}
