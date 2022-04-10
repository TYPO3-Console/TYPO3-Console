
.. include:: /Includes.rst.txt

.. The following reference was automatically generated from code. It should not
.. be changed directly.

.. _typo3_console-command-reference-database-export:

===============
database:export
===============


**Export database to stdout**

Export the database (all tables) directly to stdout.
The mysqldump binary must be available in the path for this command to work.
This obviously only works when MySQL is used as DBMS.

Tables to be excluded from the export can be specified fully qualified or with wildcards:

**Example:**


.. code-block:: shell

   typo3cms database:export -c Default -e 'cf_*' -e 'cache_*' -e '[bf]e_sessions' -e sys_log



.. code-block:: shell

   typo3cms database:export database:export -c Default -- --column-statistics=0


Arguments
=========

`additionalMysqlDumpArguments`
   Pass one or more additional arguments to the mysqldump command; see examples



Options
=======

`--exclude|-e`
   Full table name or wildcard expression to exclude from the export.

- Accept value: yes
- Is value required: yes
- Is multiple: yes
- Default: array ()

`--connection|-c`
   TYPO3 database connection name (defaults to all configured MySQL connections)

- Accept value: yes
- Is value required: yes
- Is multiple: no






