
.. include:: ../Includes.txt


.. _typo3_console-command-reference:

Command Reference
=================

.. note::

  This reference uses ``./typo3cms`` as the command to invoke. If you are on
  Windows, this will probably not work, there you need to use ``typo3cms.bat``
  instead.
  In composer based installations, the ``typo3cms`` binary will be located
  in the binary directory specified in the root composer.json (by default ``vendor/bin``)


The following reference was automatically generated from code on 2016-11-01 13:03:01


.. _`Command Reference: typo3_console`:

Extension *typo3_console*
-------------------------


.. _`Command Reference: typo3_console autocomplete`:

``autocomplete``
****************

**Generate shell auto complete script**

Inspired by and copied code from https://github.com/bamarni/symfony-console-autocomplete
See https://github.com/bamarni/symfony-console-autocomplete/blob/master/README.md
for a description how to install the script in your system.



Options
^^^^^^^

``--shell``
  "bash" or "zsh"
``--aliases``
  Aliases for the typo3cms command
``--dynamic``
  Dynamic auto completion is slower but more flexible





.. _`Command Reference: typo3_console backend:lock`:

``backend:lock``
****************

**Lock backend**

Deny backend access for **every** user (including admins).



Options
^^^^^^^

``--redirect-url``
  URL to redirect to when the backend is accessed



Related commands
^^^^^^^^^^^^^^^^

``backend:unlock``
  Unlock backend



.. _`Command Reference: typo3_console backend:lockforeditors`:

``backend:lockforeditors``
**************************

**Lock backend for editors**

Deny backend access, but only for editors.
Admins will still be able to log in and work with the backend.





Related commands
^^^^^^^^^^^^^^^^

``backend:unlockforeditors``
  Unlock backend for editors



.. _`Command Reference: typo3_console backend:unlock`:

``backend:unlock``
******************

**Unlock backend**

Allow backend access again (e.g. after having been locked with backend:lock command).





Related commands
^^^^^^^^^^^^^^^^

``backend:lock``
  Lock backend



.. _`Command Reference: typo3_console backend:unlockforeditors`:

``backend:unlockforeditors``
****************************

**Unlock backend for editors**

Allow backend access for editors again (e.g. after having been locked with backend:lockforeditors command).





Related commands
^^^^^^^^^^^^^^^^

``backend:lockforeditors``
  Lock backend for editors



.. _`Command Reference: typo3_console cache:flush`:

``cache:flush``
***************

**Flush all caches**

Flushes TYPO3 core caches first and after that, flushes caches from extensions.



Options
^^^^^^^

``--force``
  Cache is forcibly flushed (low level operations are performed)





.. _`Command Reference: typo3_console cache:flushgroups`:

``cache:flushgroups``
*********************

**Flush all caches in specified groups**

Flushes all caches in specified groups.
Valid group names are by default:

- all
- lowlevel
- pages
- system

**Example:** ``./typo3cms cache:groups pages,all``

Arguments
^^^^^^^^^

``--groups``
  An array of names (specified as comma separated values) of cache groups to flush







.. _`Command Reference: typo3_console cache:flushtags`:

``cache:flushtags``
*******************

**Flush cache by tags**

Flushes caches by tags, optionally only caches in specified groups.

**Example:** ``./typo3cms cache:flushtags news_123 pages,all``

Arguments
^^^^^^^^^

``--tags``
  Array of tags (specified as comma separated values) to flush.



Options
^^^^^^^

``--groups``
  Optional array of groups (specified as comma separated values) for which to flush tags. If no group is specified, caches of all groups are flushed.





.. _`Command Reference: typo3_console cache:listgroups`:

``cache:listgroups``
********************

**List cache groups**

Lists all registered cache groups.







.. _`Command Reference: typo3_console cleanup:updatereferenceindex`:

``cleanup:updatereferenceindex``
********************************

**Update reference index**

Updates reference index to ensure data integrity

**Example:** ``./typo3cms cleanup:updatereferenceindex --dry-run --verbose``



Options
^^^^^^^

``--dry-run``
  If set, index is only checked without performing any action
``--verbose``
  Whether or not to output results
``--show-progress``
  Whether or not to output a progress bar





.. _`Command Reference: typo3_console configuration:remove`:

``configuration:remove``
************************

**Remove configuration option**

Removes a system configuration option by path.

For this command to succeed, the configuration option(s) must be in
LocalConfiguration.php and not be overridden elsewhere.

**Example:** ``./typo3cms configuration:remove DB,EXT/EXTCONF/realurl``

Arguments
^^^^^^^^^

``--paths``
  Path to system configuration that should be removed. Multiple paths can be specified separated by comma



Options
^^^^^^^

``--force``
  If set, does not ask for confirmation





.. _`Command Reference: typo3_console configuration:set`:

``configuration:set``
*********************

**Set configuration value**

Set system configuration option value by path.

**Example:** ``./typo3cms configuration:set SYS/fileCreateMask 0664``

Arguments
^^^^^^^^^

``--path``
  Path to system configuration
``--value``
  Value for system configuration







.. _`Command Reference: typo3_console configuration:show`:

``configuration:show``
**********************

**Show configuration value**

Shows system configuration value by path.
If the currently active configuration differs from the value in LocalConfiguration.php
the difference between these values is shown.

**Example:** ``./typo3cms configuration:show DB``

Arguments
^^^^^^^^^

``--path``
  Path to system configuration option







.. _`Command Reference: typo3_console configuration:showactive`:

``configuration:showactive``
****************************

**Show active configuration value**

Shows active system configuration by path.
Shows the configuration value that is currently effective, no matter where and how it is set.

**Example:** ``./typo3cms configuration:showActive DB``

Arguments
^^^^^^^^^

``--path``
  Path to system configuration







.. _`Command Reference: typo3_console configuration:showlocal`:

``configuration:showlocal``
***************************

**Show local configuration value**

Shows local configuration option value by path.
Shows the value which is stored in LocalConfiguration.php.
Note that this value could be overridden. Use ``./typo3cms configuration:show [path]`` to see if this is the case.

**Example:** ``./typo3cms configuration:showLocal DB``

Arguments
^^^^^^^^^

``--path``
  Path to local system configuration





Related commands
^^^^^^^^^^^^^^^^

``configuration:show``
  Show configuration value



.. _`Command Reference: typo3_console database:export`:

``database:export``
*******************

**Export database to stdout**

Export the database (all tables) directly to stdout.
The mysqldump binary must be available in the path for this command to work.
This obviously only works when MySQL is used as DBMS.

**This command passes the plain text database password to the command line process.**
This means, that users that have the permission to observe running processes,
will be able to read your password.
If this imposes a security risk for you, then refrain from using this command!







.. _`Command Reference: typo3_console database:import`:

``database:import``
*******************

**Import mysql from stdin**

This means that this can not only be used to pass insert statements,
it but works as well to pass SELECT statements to it.
The mysql binary must be available in the path for this command to work.
This obviously only works when MySQL is used as DBMS.

**Example (import):** ``ssh remote.server '/path/to/typo3cms database:export' | ./typo3cms database:import``
**Example (select):** ``echo 'SELECT username from be_users WHERE admin=1;' | ./typo3cms database:import``
**Example (interactive):** ``./typo3cms database:import --interactive``

**This command passes the plain text database password to the command line process.**
This means, that users that have the permission to observe running processes,
will be able to read your password.
If this imposes a security risk for you, then refrain from using this command!



Options
^^^^^^^

``--interactive``
  Open an interactive mysql shell using the TYPO3 connection settings.





.. _`Command Reference: typo3_console database:updateschema`:

``database:updateschema``
*************************

**Update database schema**

Valid schema update types are:

- field.add
- field.change
- field.prefix
- field.drop
- table.add
- table.change
- table.prefix
- table.drop
- table.clear
- safe (includes all necessary operations, to add or change fields or tables)
- destructive (includes all operations which rename or drop fields or tables)

The list of schema update types supports wildcards to specify multiple types, e.g.:

- "*" (all updates)
- "field.*" (all field updates)
- "*.add,*.change" (all add/change updates)

To avoid shell matching all types with wildcards should be quoted.

**Example:** ``./typo3cms database:updateschema "*.add,*.change"``



Options
^^^^^^^

``--schema-update-types``
  List of schema update types (default: "safe")
``--verbose``
  If set, database queries performed are shown in output
``--dry-run``
  If set the updates are only collected and shown, but not executed





.. _`Command Reference: typo3_console documentation:generatexsd`:

``documentation:generatexsd``
*****************************

**Generate Fluid ViewHelper XSD Schema**

Generates Schema documentation (XSD) for your ViewHelpers, preparing the
file to be placed online and used by any XSD-aware editor.
After creating the XSD file, reference it in your IDE and import the namespace
in your Fluid template by adding the xmlns:* attribute(s):
``<html xmlns="http://www.w3.org/1999/xhtml" xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers" ...>``

Arguments
^^^^^^^^^

``--php-namespace``
  Namespace of the Fluid ViewHelpers without leading backslash (for example 'TYPO3\Fluid\ViewHelpers' or 'Tx_News_ViewHelpers'). NOTE: Quote and/or escape this argument as needed to avoid backslashes from being interpreted!



Options
^^^^^^^

``--xsd-namespace``
  Unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers"). Defaults to "http://typo3.org/ns/<php namespace>".
``--target-file``
  File path and name of the generated XSD schema. If not specified the schema will be output to standard output.





.. _`Command Reference: typo3_console extension:activate`:

``extension:activate``
**********************

**Activate extension(s)**

Activates one or more extensions by key.
Marks extensions as active, sets them up and clears caches for every activated extension.

Arguments
^^^^^^^^^

``--extension-keys``
  Extension keys to activate. Separate multiple extension keys with comma.







.. _`Command Reference: typo3_console extension:deactivate`:

``extension:deactivate``
************************

**Deactivate extension(s)**

Deactivates one or more extensions by key.
Marks extensions as inactive in the system and clears caches for every deactivated extension.

Arguments
^^^^^^^^^

``--extension-keys``
  Extension keys to deactivate. Separate multiple extension keys with comma.







.. _`Command Reference: typo3_console extension:dumpautoload`:

``extension:dumpautoload``
**************************

**Dump class auto-load**

Updates class loading information in non composer managed TYPO3 installations.

This command is only needed during development. The extension manager takes care
creating or updating this info properly during extension (de-)activation.







.. _`Command Reference: typo3_console extension:list`:

``extension:list``
******************

**List extensions that are available in the system**





Options
^^^^^^^

``--active``
  Only show active extensions
``--inactive``
  Only show inactive extensions
``--raw``
  Enable machine readable output (just extension keys separated by line feed)





.. _`Command Reference: typo3_console extension:setup`:

``extension:setup``
*******************

**Set up extension(s)**

Sets up one or more extensions by key.
Set up means:

- Database migrations and additions
- Importing files and data
- Writing default extension configuration

Arguments
^^^^^^^^^

``--extension-keys``
  Extension keys to set up. Separate multiple extension keys with comma.







.. _`Command Reference: typo3_console extension:setupactive`:

``extension:setupactive``
*************************

**Set up all active extensions**

Sets up all extensions that are marked as active in the system.

This command is especially useful for deployment, where extensions
are already marked as active, but have not been set up yet or might have changed. It ensures every necessary
setup step for the (changed) extensions is performed.
As an additional benefit no caches are flushed, which significantly improves performance of this command
and avoids unnecessary cache clearing.





Related commands
^^^^^^^^^^^^^^^^

``extension:setup``
  Set up extension(s)
``install:generatepackagestates``
  Generate PackageStates.php file
``cache:flush``
  Flush all caches



.. _`Command Reference: typo3_console frontend:request`:

``frontend:request``
********************

**Submit frontend request**

Submits a frontend request to TYPO3 on the specified URL.

Arguments
^^^^^^^^^

``--request-url``
  URL to make a frontend request.







.. _`Command Reference: typo3_console help`:

``help``
********

**Help**

Display help for a command

The help command displays help for a given command:
./typo3cms help <command identifier>



Options
^^^^^^^

``--command-identifier``
  Identifier of a command for more details
``--raw``
  Raw output of commands only





.. _`Command Reference: typo3_console install:fixfolderstructure`:

``install:fixfolderstructure``
******************************

**Fix folder structure**

Automatically create files and folders, required for a TYPO3 installation.

This command is great e.g. for creating the typo3temp folder structure during deployment







.. _`Command Reference: typo3_console install:generatepackagestates`:

``install:generatepackagestates``
*********************************

**Generate PackageStates.php file**

Generates and writes ``typo3conf/PackageStates.php`` file.
Goal is to not have this file in version control, but generate it on ``composer install``.

Marks the following extensions as active:

- Third party extensions
- All core extensions that are required (or part of minimal usable system)
- All core extensions which are provided in the TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS environment variable. Extension keys in this variable must be separated by comma and without spaces.

**Example:** ``TYPO3_ACTIVE_FRAMEWORK_EXTENSIONS="info,info_pagetsconfig" ./typo3cms install:generatepackagestates``



Options
^^^^^^^

``--remove-inactive``
  Inactive extensions are **removed** from ``typo3/sysext``. **Handle with care!**
``--activate-default``
  If true, ``typo3/cms`` extensions that are marked as TYPO3 factory default, will be activated, even if not in the list of configured active framework extensions.





.. _`Command Reference: typo3_console install:setup`:

``install:setup``
*****************

**TYPO3 Setup**

Use as command line replacement for the web installation process.
Manually enter details on the command line or non interactive for automated setups.



Options
^^^^^^^

``--non-interactive``
  If specified, optional arguments are not requested, but default values are assumed.
``--force``
  Force installation of TYPO3, even if ``LocalConfiguration.php`` file already exists.
``--database-user-name``
  User name for database server
``--database-user-password``
  User password for database server
``--database-host-name``
  Host name of database server
``--database-port``
  TCP Port of database server
``--database-socket``
  Unix Socket to connect to (if localhost is given as hostname and this is kept empty, a socket connection will be established)
``--database-name``
  Name of the database
``--use-existing-database``
  If set an empty database with the specified name will be used. Otherwise a database with the specified name is created.
``--admin-user-name``
  User name of the administrative backend user account to be created
``--admin-password``
  Password of the administrative backend user account to be created
``--site-name``
  Site Name
``--site-setup-type``
  Can be either ``no`` (which unsurprisingly does nothing at all), ``site`` (which creates an empty root page and setup) or ``dist`` (which loads a list of distributions you can install)





.. _`Command Reference: typo3_console scheduler:run`:

``scheduler:run``
*****************

**Run scheduler**

Executes tasks that are registered in the scheduler module.

**Example:** ``./typo3cms scheduler:run 42 --force``



Options
^^^^^^^

``--task-id``
  Uid of the task that should be executed (instead of all scheduled tasks)
``--force``
  The execution can be forced with this flag. The task will then be executed even if it is not scheduled for execution yet. Only works, when a task is specified.





