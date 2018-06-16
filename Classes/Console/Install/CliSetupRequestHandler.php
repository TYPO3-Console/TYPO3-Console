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

use Helhum\Typo3Console\Command\InstallCommandController;
use Helhum\Typo3Console\Mvc\Cli\Command;
use Helhum\Typo3Console\Mvc\Cli\CommandArgumentDefinition;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Symfony\Component\Console\Exception\RuntimeException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

/**
 * This class acts as facade for the install tool step actions.
 * It glues together the execution of these actions with the user interaction on the command line
 * @deprecated With 5.4 will be removed with 6.0. Use \Helhum\Typo3Console\Install\Action\InstallActionDispatcher instead.
 */
class CliSetupRequestHandler
{
    const INSTALL_COMMAND_CONTROLLER_CLASS = \Helhum\Typo3Console\Command\InstallCommandController::class;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CommandDispatcher
     */
    private $commandDispatcher;

    /**
     * @var array List of necessary installation steps. Order is important!
     */
    private $installationActions = [
        'environmentAndFolders',
        'databaseConnect',
        'databaseSelect',
        'databaseData',
        'defaultConfiguration',
    ];

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var CliMessageRenderer
     */
    private $messageRenderer;

    /**
     * @var array
     */
    private $givenRequestArguments = [];

    /**
     * @var bool
     */
    private $interactiveSetup = true;

    public function __construct(
        ConsoleOutput $output = null,
        CommandDispatcher $commandDispatcher = null,
        ObjectManagerInterface $objectManager = null,
        CliMessageRenderer $messageRenderer = null
    ) {
        $this->output = $output ?: new ConsoleOutput();
        $this->commandDispatcher = $commandDispatcher ?: CommandDispatcher::createFromCommandRun();
        $this->objectManager = $objectManager ?: GeneralUtility::makeInstance(ObjectManager::class);
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
            $actionArguments = [
                'arguments' => [],
                'options' => [],
            ];
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
            $response = $this->executeActionWithArguments($actionName, $actionArguments['arguments'], $actionArguments['options']);
            if ($this->checkIfActionNeedsExecution($actionName)->actionNeedsExecution()) {
                // @deprecated Can be removed once TYPO3 8.7 support is removed. Then it will be safe to call the action only once
                // and just assume it completed
                $response = $this->executeActionWithArguments($actionName, $actionArguments['arguments'], $actionArguments['options']);
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
     * @param array $options Options for the install step
     * @return InstallStepResponse
     */
    private function executeActionWithArguments($actionName, array $arguments = [], array $options = [])
    {
        $actionName = strtolower($actionName);
        // Arguments must come first then options, to avoid argument values to be passed to boolean flags
        $arguments = array_merge($arguments, $options);
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
            $currentActionArguments['arguments'][] = $value;
        } else {
            if ($argumentDefinition->acceptsValue()) {
                $currentActionArguments['options'][$argumentDefinition->getDashedName()] = $value;
            } else {
                if ((bool)$value) {
                    $currentActionArguments['options'][] = $argumentDefinition->getDashedName();
                }
            }
        }
    }
}
