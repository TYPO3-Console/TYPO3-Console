
.. include:: ../Includes.txt



.. _typo3_console-command-reference-upgrade-all:

The following reference was automatically generated from code.


===========
upgrade:all
===========


**Execute all upgrade wizards**

Executes all upgrade wizards that are scheduled for execution.
Arguments can be provided for wizards that need confirmation.

**Examples:**

  `typo3cms upgrade:all --arguments adminpanelExtension[confirm]=0`

  `typo3cms upgrade:all --arguments adminpanelExtension[confirm]=0,funcExtension[confirm]=0`

  `typo3cms upgrade:all --arguments confirm=0`



Options
~~~~~~~

`--arguments|-a`
   Arguments for the wizard prefixed with the identifier, multiple arguments separated with comma.

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: array ()





