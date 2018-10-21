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

use Helhum\Typo3Console\Install\StepsConfig;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Output\TrackableOutput;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * This class acts as facade for the install tool step actions.
 * It glues together the execution of these actions with the user interaction on the command line
 */
class InstallActionDispatcher
{
    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var StepsConfig
     */
    private $stepsConfig;

    /**
     * @var InstallActionFactory
     */
    private $installActionFactory;

    public function __construct(
        ConsoleOutput $output = null,
        CommandDispatcher $commandDispatcher = null,
        StepsConfig $stepsConfig = null,
        InstallActionFactory $installActionFactory = null
    ) {
        $this->output = $output ?: new ConsoleOutput();
        $commandDispatcher = $commandDispatcher ?: CommandDispatcher::createFromCommandRun();
        $this->stepsConfig = $stepsConfig ?: new StepsConfig();
        $this->installActionFactory = $installActionFactory ?: new InstallActionFactory($this->output, $commandDispatcher);
    }

    public function dispatch(array $givenArguments, array $options = [], string $stepsConfigFile = null): bool
    {
        try {
            $installSteps = $this->stepsConfig->getInstallSteps($stepsConfigFile);
            $interactiveSetup = $options['interactive'] ?? $this->output->getSymfonyConsoleInput()->isInteractive();
            $consoleOutput = $this->output->getSymfonyConsoleOutput();

            foreach ($installSteps as $actionName => $actionDefinition) {
                $skipAction = $actionDefinition['skip'] ?? false;
                if ($skipAction) {
                    continue;
                }

                $action = $this->installActionFactory->create($actionDefinition['type']);
                $success = true;
                $errorCount = 0;
                $options['actionName'] = $actionName;
                $options['givenArguments'] = $givenArguments;

                do {
                    $this->output->outputLine(sprintf('➤ <info>%s</info>', $actionDefinition['description'] ?? $actionName));
                    $consoleOutput->startTracking();

                    $shouldExecute = $action->shouldExecute($actionDefinition, $options);
                    if ($shouldExecute) {
                        $success = $action->execute($actionDefinition, $options);
                    }

                    if (!$interactiveSetup && !$success && $shouldExecute && $errorCount++ > 10) {
                        throw new RuntimeException(sprintf('Tried to dispatch "%s" %d times.', $actionName, $errorCount), 1405269518);
                    }
                } while (!$success && $shouldExecute);

                $this->outputSuccessMessage($consoleOutput, $shouldExecute, $actionDefinition['description'] ?? $actionName);
            }
        } catch (InstallationFailedException $e) {
            return false;
        }

        return true;
    }

    private function outputSuccessMessage(TrackableOutput $consoleOutput, bool $shouldExecute, string $description)
    {
        $canReplaceActionMark = $consoleOutput->isDecorated() && !$consoleOutput->wasOutputTracked();
        $replaceSequence = "\r\033[K\033[1A\r";
        $type = 'success';
        $okLabel = '';

        if (!$canReplaceActionMark) {
            $replaceSequence = '';
            $okLabel = ' Ok';
        }
        if (!$shouldExecute) {
            $type = 'comment';
            $okLabel = ' <comment>Skipped</comment> ' . $description;
        }

        $this->output->outputLine('%1$s<%2$s>✔</%2$s>%3$s', [$replaceSequence, $type, $okLabel]);
    }
}
