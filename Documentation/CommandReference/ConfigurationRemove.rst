
.. include:: ../Includes.txt



.. _typo3_console-command-reference-configuration-remove:

The following reference was automatically generated from code.


====================
configuration:remove
====================


**Remove configuration option**

Removes a system configuration option by path.

For this command to succeed, the configuration option(s) must be in
LocalConfiguration.php and not be overridden elsewhere.

**Example:**

  `typo3cms configuration:remove DB,EXT/EXTCONF/realurl`

Arguments
~~~~~~~~~

`paths`
   Path to system configuration that should be removed. Multiple paths can be specified separated by comma



Options
~~~~~~~

`--force`
   If set, does not ask for confirmation

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false





