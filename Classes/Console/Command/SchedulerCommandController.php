<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command;

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

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use TYPO3\CMS\Scheduler\Scheduler;

class SchedulerCommandController extends CommandController
{
    /**
     * @var Scheduler
     */
    protected $scheduler;

    public function __construct(Scheduler $scheduler)
    {
        $this->scheduler = $scheduler;
    }

    /**
     * Run scheduler
     *
     * Executes tasks that are registered in the scheduler module.
     *
     * <b>Example:</b> <code>%command.full_name% 42 --force</code>
     *
     * @param int $task Uid of the task that should be executed (instead of all scheduled tasks)
     * @param bool $force The execution can be forced with this flag. The task will then be executed even if it is not scheduled for execution yet. Only works, when a task is specified.
     * @param int $taskId Deprecated option (same as --task)
     */
    public function runCommand($task = null, $force = false, $taskId = null)
    {
        if ($taskId !== null) {
            // @deprecated in 5.0 will be removed in 6.0
            $this->outputLine('<warning>Using --task-id is deprecated. Please use --task instead.</warning>');
            $task = $taskId;
        }
        if ($task !== null) {
            if ($task <= 0) {
                $this->outputLine('Task Id must be higher than zero.');
                $this->quit(1);
            }
            $this->executeSingleTask($task, $force);
        } else {
            if ($force) {
                $this->outputLine('Execution can only be forced when a single task is specified.');
                $this->quit(2);
            }
            $this->executeScheduledTasks();
        }
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
                /** @var $task \TYPO3\CMS\Scheduler\Task\AbstractTask */
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
