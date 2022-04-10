
.. include:: /Includes.rst.txt

.. The following reference was automatically generated from code. It should not
.. be changed directly.

.. _typo3_console-command-reference-database-import:

===============
database:import
===============


**Import mysql queries from stdin**

This means that this can not only be used to pass insert statements,
it but works as well to pass SELECT statements to it.
The mysql binary must be available in the path for this command to work.
This obviously only works when MySQL is used as DBMS.

**Example (import):**


.. code-block:: shell

   ssh remote.server '/path/to/typo3cms database:export' | typo3cms database:import

**Example (select):**


.. code-block:: shell

   echo 'SELECT username from be_users WHERE admin=1;' | typo3cms database:import

**Example (interactive):**


.. code-block:: shell

   typo3cms database:import --interactive




Options
=======

`--interactive`
   Open an interactive mysql shell using the TYPO3 connection settings.

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false

`--connection`
   TYPO3 database connection name

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: 'Default'





