
.. include:: /Includes.rst.txt

.. The following reference was automatically generated from code. It should not
.. be changed directly.

.. _typo3_console-command-reference-configuration-showlocal:

=======================
configuration:showlocal
=======================


**Show local configuration value**

Shows local configuration option value by path.
Shows the value which is stored in LocalConfiguration.php.
Note that this value could be overridden. Use `typo3cms configuration:show <path>` to see if this is the case.

**Example:**


.. code-block:: shell

   typo3cms configuration:showlocal DB


Arguments
=========

`path`
   Path to local system configuration



Options
=======

`--json`
   If set, the configuration is shown as JSON

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false




Related commands
================


*   :ref:`typo3_console-command-reference-configuration-show`


