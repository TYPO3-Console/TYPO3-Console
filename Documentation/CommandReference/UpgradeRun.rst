
.. include:: ../Includes.txt



.. _typo3_console-command-reference-upgrade-run:

The following reference was automatically generated from code.


===========
upgrade:run
===========


**Run a single upgrade wizard, or all wizards that are scheduled for execution**

Runs upgrade wizards.

If "all" is specified as wizard identifier, all wizards that are scheduled are executed.
When no identifier is specified a select UI is presented to select a wizard out of all scheduled ones.

**Examples:**

  `typo3cms upgrade:run all`

  `typo3cms upgrade:run all --no-interaction --confirm all --deny typo3DbLegacyExtension --deny funcExtension`

  `typo3cms upgrade:run all --no-interaction --deny all`

  `typo3cms upgrade:run argon2iPasswordHashes --confirm all`


Arguments
~~~~~~~~~

`wizardIdentifier`
   



Options
~~~~~~~

`--confirm|-y`
   Identifier of the wizard, that should be confirmed. Keyword "all" confirms all wizards.

- Accept value: yes
- Is value required: yes
- Is multiple: yes
- Default: array ()

`--deny|-d`
   Identifier of the wizard, that should be denied. Keyword "all" denies all wizards.

- Accept value: yes
- Is value required: yes
- Is multiple: yes
- Default: array ()

`--force|-f`
   Force a single wizard to run, despite being marked as executed before. Has no effect on "all"

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false





