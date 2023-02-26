
.. include:: /Includes.rst.txt

.. The following reference was automatically generated from code. It should not
.. be changed directly.

.. _typo3_console-command-reference-configuration-remove:

====================
configuration:remove
====================


**Remove configuration value**

Removes a system configuration option by path.

For this command to succeed, the configuration option(s) must be in
system configuration file and not be overridden elsewhere.

**Example:**


.. code-block:: shell

   typo3 configuration:remove DB,EXT/EXTCONF/realurl


Arguments
=========

`paths`
   Path to system configuration that should be removed. Multiple paths can be specified separated by comma



Options
=======

`--force`
   If set, does not ask for confirmation

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false





