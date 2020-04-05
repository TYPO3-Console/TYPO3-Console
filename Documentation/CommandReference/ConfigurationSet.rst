
.. include:: ../Includes.txt



.. _typo3_console-command-reference-configuration-set:

The following reference was automatically generated from code.


=================
configuration:set
=================


**Set configuration value**

Set system configuration option value by path.

**Examples:**

  `typo3cms configuration:set SYS/fileCreateMask 0664`

  `typo3cms configuration:set EXTCONF/processor_enabled true --json`

  `typo3cms configuration:set EXTCONF/lang/availableLanguages '["de", "fr"]' --json`

  `typo3cms configuration:set configuration:set BE/adminOnly -- -1`

Arguments
~~~~~~~~~

`path`
   Path to system configuration
`value`
   Value for system configuration



Options
~~~~~~~

`--json`
   Treat value as JSON (also makes it possible to force datatypes for value)

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false





