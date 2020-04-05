
.. include:: ../Includes.txt



.. _typo3_console-command-reference-scheduler-run:

The following reference was automatically generated from code.


=============
scheduler:run
=============


**Run scheduler**

Executes tasks that are registered in the scheduler module.

**Example:**

  `typo3cms scheduler:run --task 42 --force`



Options
~~~~~~~

`--task`
   Uid of the task that should be executed (instead of all scheduled tasks)

- Accept value: yes
- Is value required: yes
- Is multiple: no


`--force`
   The execution can be forced with this flag. The task will then be executed even if it is not scheduled for execution yet. Only works, when a task is specified.

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false

`--task-id`
   Deprecated option (same as --task)

- Accept value: yes
- Is value required: yes
- Is multiple: no






