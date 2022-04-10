
.. include:: /Includes.rst.txt

.. The following reference was automatically generated from code. It should not
.. be changed directly.

.. _typo3_console-command-reference-upgrade-run:

===========
upgrade:run
===========


**Run a single upgrade wizard, or all wizards that are scheduled for execution**

Runs upgrade wizards.

If "all" is specified as wizard identifier, all wizards that are scheduled are executed.
When no identifier is specified a select UI is presented to select a wizard out of all scheduled ones.

**Examples:**


.. code-block:: shell

   typo3cms upgrade:run all


.. code-block:: shell

   typo3cms upgrade:run all --confirm all


.. code-block:: shell

   typo3cms upgrade:run argon2iPasswordHashes --confirm all


.. code-block:: shell

   typo3cms upgrade:run all --confirm all --deny typo3DbLegacyExtension --deny funcExtension


.. code-block:: shell

   typo3cms upgrade:run all --deny all


.. code-block:: shell

   typo3cms upgrade:run all --no-interaction --deny all --confirm argon2iPasswordHashes


Arguments
=========

`wizardIdentifiers`
   One or more wizard identifiers to run



Options
=======

`--confirm|-y`
   Identifier of the wizard, that should be confirmed. Keyword "all" confirms all wizards.

- Accept value: yes
- Is value required: yes
- Is multiple: yes
- Default: array ()

`--deny|-d`
   Identifier of the wizard, that should be denied. Keyword "all" denies all wizards. Deny takes precedence except when "all" is specified.

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

`--force-row-updater`
   Identifier of the row updater to be forced to run. Has only effect on "databaseRowsUpdateWizard"

- Accept value: yes
- Is value required: yes
- Is multiple: yes
- Default: array ()





