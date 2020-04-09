<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Scheduler;

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

use Helhum\Typo3Console\Command\AbstractConvertedCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Scheduler;

class SchedulerRunCommand extends AbstractConvertedCommand
{
    /**
     * @var Scheduler
     */
    private $scheduler;

    protected function configure()
    {
        $this->setDescription('Run scheduler');
        $this->setHelp('Executes tasks that are registered in the scheduler module.

<b>Example:</b>

  <code>%command.full_name% --task 42 --force</code>');
        /** @deprecated Will be removed with 6.0 */
        $this->setDefinition($this->createCompleteInputDefinition());
    }

    /**
     * @deprecated Will be removed with 6.0
     */
    protected function createNativeDefinition(): array
    {
        return [
            new InputOption(
                'task',
                null,
                InputOption::VALUE_REQUIRED,
                'Uid of the task that should be executed (instead of all scheduled tasks)'
            ),
            new InputOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'The execution can be forced with this flag. The task will then be executed even if it is not scheduled for execution yet. Only works, when a task is specified.'
            ),
            new InputOption(
                'task-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Deprecated option (same as --task)'
            ),
        ];
    }

    /**
     * @deprecated will be removed with 6.0
     */
    protected function handleDeprecatedArgumentsAndOptions(InputInterface $input, OutputInterface $output)
    {
        // nothing to do here
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->scheduler = GeneralUtility::makeInstance(Scheduler::class);
        $task = $input->getOption('task');
        $force = $input->getOption('force');
        if ($task === null && $task = $input->getOption('task-id')) {
            // @deprecated in 5.0 will be removed in 6.0
            $output->writeln('<warning>Using --task-id is deprecated. Please use --task instead.</warning>');
            $task = $input->getOption('task-id');
        }
        if ($task === null) {
            if ($force) {
                $output->writeln('Execution can only be forced when a single task is specified.');

                return 2;
            }
            $this->executeScheduledTasks();

            return 0;
        }
        if ($task <= 0) {
            $output->writeln('Task Id must be higher than zero.');

            return 1;
        }
        $this->executeSingleTask($task, $force);

        return 0;
    }

    /**
     * Execute all scheduled tasks
     */
    protected function executeScheduledTasks()
    {
        // Loop as long as there are tasks
        do {
            // Try getting the next task and execute it
            // If there are no more tasks to execute, an exception is thrown by \TYPO3\CMS\Scheduler\Scheduler::fetchTask()
            try {
                $task = $this->scheduler->fetchTask();
                $hasTask = true;
                try {
                    $this->scheduler->executeTask($task);
                } catch (\Exception $e) {
                    // We ignore any exception that may have been thrown during execution,
                    // as this is a background process.
                    // The exception message has been recorded to the database anyway
                    continue;
                }
            } catch (\OutOfBoundsException $e) {
                $hasTask = false;
            } catch (\UnexpectedValueException $e) {
                continue;
            }
        } while ($hasTask);
        // Record the run in the system registry
        $this->scheduler->recordLastRun();
    }

    /**
     * Execute a single task
     *
     * @param int $taskId
     * @param bool $forceExecution
     */
    protected function executeSingleTask($taskId, $forceExecution)
    {
        // Force the execution of the task even if it is disabled or no execution scheduled
        if ($forceExecution) {
            $task = $this->scheduler->fetchTask($taskId);
        } else {
            $whereClause = 'uid = ' . (int)$taskId . ' AND nextexecution != 0 AND nextexecution <= ' . (int)$GLOBALS['EXEC_TIME'];
            list($task) = $this->scheduler->fetchTasksWithCondition($whereClause);
        }
        if ($this->scheduler->isValidTaskObject($task)) {
            try {
                $this->scheduler->executeTask($task);
            } finally {
                // Record the run in the system registry
                $this->scheduler->recordLastRun('cli-by-id');
            }
        }
    }
}
