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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Scheduler\Scheduler;

class RunCommand extends Command
{
    /**
     * @var Scheduler
     */
    private $scheduler;

    public function __construct(string $name = null, Scheduler $scheduler = null)
    {
        parent::__construct($name);
        $this->scheduler = $scheduler;
    }

    protected function configure()
    {
        $this->setDescription('Run scheduler');
        $this->setHelp('Executes tasks that are registered in the scheduler module.

<b>Example:</b> <code>%command.full_name% 42 --force</code>');
        $this->addOption(
            'task',
            null,
            InputOption::VALUE_REQUIRED,
            'Uid of the task that should be executed (instead of all scheduled tasks)'
        );
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'The execution can be forced with this flag. The task will then be executed even if it is not scheduled for execution yet. Only works, when a task is specified.'
        );
        $this->addOption(
            'task-id',
            null,
            InputOption::VALUE_REQUIRED,
            'Deprecated option (same as --task)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $task = $input->hasOption('task') ? $input->getOption('task') : null;
        $force = $input->hasOption('force') ? $input->getOption('force') : false;
        if ($input->hasOption('task-id')) {
            // @deprecated in 5.0 will be removed in 6.0
            $io->warning('Using --task-id is deprecated. Please use --task instead.');
            $task = $input->getOption('task-id');
        }
        if ($task !== null) {
            if ($task <= 0) {
                $io->error('Task Id must be higher than zero.');

                return 1;
            }
            $this->executeSingleTask($task, $force);
        } else {
            if ($force) {
                $io->error('Execution can only be forced when a single task is specified.');

                return 2;
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
