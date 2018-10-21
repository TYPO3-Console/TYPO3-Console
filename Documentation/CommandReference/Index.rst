
.. include:: ../Includes.txt



.. _typo3_console-command-reference:

=================
Command Reference
=================

.. note::

   This reference uses `typo3cms` as the command to invoke. If you are on
   Windows, this will probably not work, there you need to use `typo3cms.bat`
   instead. In Composer based installations, the `typo3cms` binary will be
   located in the binary directory specified in the root composer.json (by
   default `vendor/bin`)


The following reference was automatically generated from code.



Application Options
-------------------

The following options can be used with every command:

`--help|-h`
   Display this help message

`--quiet|-q`
   Do not output any message

`--verbose|-v|-vv|-vvv`
   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

`--ansi`
   Force ANSI output

`--no-ansi`
   Disable ANSI output

`--no-interaction|-n`
   Do not ask any interactive question



.. _`Command Reference: typo3_console`:

Available Commands
------------------


.. _`Command Reference: typo3_console help`:

`help`
------

**Displays help for a command**

The `help` command displays help for a given command:

  `php typo3cms help list`

You can also output the help in other formats by using the **--format** option:

  `php typo3cms help --format=xml list`

To display the list of available commands, please use the `list` command.

Arguments
~~~~~~~~~

`command_name`
   The command name



Options
~~~~~~~

`--format`
   The output format (txt, xml, json, or md)

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: 'txt'

`--raw`
   To output raw command help

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false






.. _`Command Reference: typo3_console list`:

`list`
------

**Lists commands**

The `list` command lists all commands:

  `php typo3cms list`

You can also display the commands for a specific namespace:

  `php typo3cms list test`

You can also output the information in other formats by using the **--format** option:

  `php typo3cms list --format=xml`

It's also possible to get raw list of commands (useful for embedding command runner):

  `php typo3cms list --raw`

Arguments
~~~~~~~~~

`namespace`
   The namespace name



Options
~~~~~~~

`--raw`
   To output raw command list

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false

`--format`
   The output format (txt, xml, json, or md)

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: 'txt'






.. _`Command Reference: typo3_console backend:createadmin`:

`backend:createadmin`
---------------------

**Create admin backend user**

Create a new user with administrative access.

Arguments
~~~~~~~~~

`username`
   Username of the user
`password`
   Password of the user







.. _`Command Reference: typo3_console backend:lock`:

`backend:lock`
--------------

**Lock backend**

Deny backend access for **every** user (including admins).



Options
~~~~~~~

`--redirect-url`
   URL to redirect to when the backend is accessed

- Accept value: yes
- Is value required: yes
- Is multiple: no





Related commands
~~~~~~~~~~~~~~~~

`backend:unlock`
  Unlock backend



.. _`Command Reference: typo3_console backend:lockforeditors`:

`backend:lockforeditors`
------------------------

**Lock backend for editors**

Deny backend access, but only for editors.
Admins will still be able to log in and work with the backend.





Related commands
~~~~~~~~~~~~~~~~

`backend:unlockforeditors`
  Unlock backend for editors



.. _`Command Reference: typo3_console backend:unlock`:

`backend:unlock`
----------------

**Unlock backend**

Allow backend access again (e.g. after having been locked with backend:lock command).





Related commands
~~~~~~~~~~~~~~~~

`backend:lock`
  Lock backend



.. _`Command Reference: typo3_console backend:unlockforeditors`:

`backend:unlockforeditors`
--------------------------

**Unlock backend for editors**

Allow backend access for editors again (e.g. after having been locked with backend:lockforeditors command).





Related commands
~~~~~~~~~~~~~~~~

`backend:lockforeditors`
  Lock backend for editors



.. _`Command Reference: typo3_console cache:flush`:

`cache:flush`
-------------

**Flush all caches**

Flushes TYPO3 core caches first and after that, flushes caches from extensions.



Options
~~~~~~~

`--force`
   Cache is forcibly flushed (low level operations are performed)

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false

`--files-only`
   Only file caches are flushed

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false






.. _`Command Reference: typo3_console cache:flushgroups`:

`cache:flushgroups`
-------------------

**Flush all caches in specified groups**

Flushes all caches in specified groups.
Valid group names are by default:

- all
- lowlevel
- pages
- system

**Example:** `typo3cms cache:flushgroups pages,all`

Arguments
~~~~~~~~~

`groups`
   An array of names (specified as comma separated values) of cache groups to flush







.. _`Command Reference: typo3_console cache:flushtags`:

`cache:flushtags`
-----------------

**Flush cache by tags**

Flushes caches by tags, optionally only caches in specified groups.

**Example:** `typo3cms cache:flushtags news_123 --groups pages,all`

Arguments
~~~~~~~~~

`tags`
   Array of tags (specified as comma separated values) to flush.



Options
~~~~~~~

`--groups`
   Optional array of groups (specified as comma separated values) for which to flush tags. If no group is specified, caches of all groups are flushed.

- Accept value: yes
- Is value required: yes
- Is multiple: no







.. _`Command Reference: typo3_console cache:listgroups`:

`cache:listgroups`
------------------

**List cache groups**

Lists all registered cache groups.







.. _`Command Reference: typo3_console cleanup:updatereferenceindex`:

`cleanup:updatereferenceindex`
------------------------------

**Update reference index**

Updates reference index to ensure data integrity

**Example:** `typo3cms cleanup:updatereferenceindex --dry-run --verbose`



Options
~~~~~~~

`--dry-run`
   If set, index is only checked without performing any action

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false

`--show-progress`
   Whether or not to output a progress bar

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false






.. _`Command Reference: typo3_console configuration:remove`:

`configuration:remove`
----------------------

**Remove configuration option**

Removes a system configuration option by path.

For this command to succeed, the configuration option(s) must be in
LocalConfiguration.php and not be overridden elsewhere.

**Example:** `typo3cms configuration:remove DB,EXT/EXTCONF/realurl`

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






.. _`Command Reference: typo3_console configuration:set`:

`configuration:set`
-------------------

**Set configuration value**

Set system configuration option value by path.

**Examples:**
`typo3cms configuration:set SYS/fileCreateMask 0664`
`typo3cms configuration:set EXTCONF/processor_enabled true --json`
`typo3cms configuration:set EXTCONF/lang/availableLanguages '["de", "fr"]' --json`

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






.. _`Command Reference: typo3_console configuration:show`:

`configuration:show`
--------------------

**Show configuration value**

Shows system configuration value by path.
If the currently active configuration differs from the value in LocalConfiguration.php
the difference between these values is shown.

**Example:** `typo3cms configuration:show DB`

Arguments
~~~~~~~~~

`path`
   Path to system configuration option







.. _`Command Reference: typo3_console configuration:showactive`:

`configuration:showactive`
--------------------------

**Show active configuration value**

Shows active system configuration by path.
Shows the configuration value that is currently effective, no matter where and how it is set.

**Example:** `typo3cms configuration:showactive DB --json`

Arguments
~~~~~~~~~

`path`
   Path to system configuration



Options
~~~~~~~

`--json`
   If set, the configuration is shown as JSON

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false






.. _`Command Reference: typo3_console configuration:showlocal`:

`configuration:showlocal`
-------------------------

**Show local configuration value**

Shows local configuration option value by path.
Shows the value which is stored in LocalConfiguration.php.
Note that this value could be overridden. Use `typo3cms configuration:show <path>` to see if this is the case.

**Example:** `typo3cms configuration:showlocal DB`

Arguments
~~~~~~~~~

`path`
   Path to local system configuration



Options
~~~~~~~

`--json`
   If set, the configuration is shown as JSON

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false




Related commands
~~~~~~~~~~~~~~~~

`configuration:show`
  Show configuration value



.. _`Command Reference: typo3_console database:export`:

`database:export`
-----------------

**Export database to stdout**

Export the database (all tables) directly to stdout.
The mysqldump binary must be available in the path for this command to work.
This obviously only works when MySQL is used as DBMS.

Tables to be excluded from the export can be specified fully qualified or with wildcards:

**Example:** `typo3cms database:export -c Default -e 'cf_*' -e 'cache_*' -e '[bf]e_sessions' -e sys_log`



Options
~~~~~~~

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







.. _`Command Reference: typo3_console database:import`:

`database:import`
-----------------

**Import mysql queries from stdin**

This means that this can not only be used to pass insert statements,
it but works as well to pass SELECT statements to it.
The mysql binary must be available in the path for this command to work.
This obviously only works when MySQL is used as DBMS.

**Example (import):** `ssh remote.server '/path/to/typo3cms database:export' | typo3cms database:import`
**Example (select):** `echo 'SELECT username from be_users WHERE admin=1;' | typo3cms database:import`
**Example (interactive):** `typo3cms database:import --interactive`



Options
~~~~~~~

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






.. _`Command Reference: typo3_console database:updateschema`:

`database:updateschema`
-----------------------

**Update database schema (TYPO3 Database Compare)**

Compares the current database schema with schema definition
from extensions's ext_tables.sql files and updates the schema based on the definition.

Valid schema update types are:

- field.add
- field.change
- field.prefix
- field.drop
- table.add
- table.change
- table.prefix
- table.drop
- safe (includes all necessary operations, to add or change fields or tables)
- destructive (includes all operations which rename or drop fields or tables)

The list of schema update types supports wildcards to specify multiple types, e.g.:

- "*" (all updates)
- "field.*" (all field updates)
- "*.add,*.change" (all add/change updates)

To avoid shell matching all types with wildcards should be quoted.

**Example:** `typo3cms database:updateschema "*.add,*.change"`

Arguments
~~~~~~~~~

`schemaUpdateTypes`
   List of schema update types (default: "safe")



Options
~~~~~~~

`--dry-run`
   If set the updates are only collected and shown, but not executed

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false






.. _`Command Reference: typo3_console documentation:generatexsd`:

`documentation:generatexsd`
---------------------------

**Generate Fluid ViewHelper XSD Schema**

Generates Schema documentation (XSD) for your ViewHelpers, preparing the
file to be placed online and used by any XSD-aware editor.
After creating the XSD file, reference it in your IDE and import the namespace
in your Fluid template by adding the xmlns:* attribute(s):
`<html xmlns="http://www.w3.org/1999/xhtml" xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers" ...>`

Arguments
~~~~~~~~~

`phpNamespace`
   Namespace of the Fluid ViewHelpers without leading backslash (for example 'TYPO3\Fluid\ViewHelpers' or 'Tx_News_ViewHelpers'). NOTE: Quote and/or escape this argument as needed to avoid backslashes from being interpreted!



Options
~~~~~~~

`--xsd-namespace`
   Unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers"). Defaults to "http://typo3.org/ns/<php namespace>".

- Accept value: yes
- Is value required: yes
- Is multiple: no


`--target-file`
   File path and name of the generated XSD schema. If not specified the schema will be output to standard output.

- Accept value: yes
- Is value required: yes
- Is multiple: no







.. _`Command Reference: typo3_console extension:activate`:

`extension:activate`
--------------------

**Activate extension(s)**

Activates one or more extensions by key.
Marks extensions as active, sets them up and clears caches for every activated extension.

This command is deprecated (and hidden) in Composer mode.

Arguments
~~~~~~~~~

`extensionKeys`
   Extension keys to activate. Separate multiple extension keys with comma.







.. _`Command Reference: typo3_console extension:deactivate`:

`extension:deactivate`
----------------------

**Deactivate extension(s)**

Deactivates one or more extensions by key.
Marks extensions as inactive in the system and clears caches for every deactivated extension.

This command is deprecated (and hidden) in Composer mode.

Arguments
~~~~~~~~~

`extensionKeys`
   Extension keys to deactivate. Separate multiple extension keys with comma.







.. _`Command Reference: typo3_console extension:dumpautoload`:

`extension:dumpautoload`
------------------------

**Dump class auto-load**

Updates class loading information in non Composer managed TYPO3 installations.

This command is only needed during development. The extension manager takes care
creating or updating this info properly during extension (de-)activation.

This command is only available in non composer mode.







.. _`Command Reference: typo3_console extension:list`:

`extension:list`
----------------

**List extensions that are available in the system**





Options
~~~~~~~

`--active`
   Only show active extensions

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false

`--inactive`
   Only show inactive extensions

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false

`--raw`
   Enable machine readable output (just extension keys separated by line feed)

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false






.. _`Command Reference: typo3_console extension:removeinactive`:

`extension:removeinactive`
--------------------------

**Removes all extensions that are not marked as active**

Directories of inactive extension are **removed** from `typo3/sysext` and `typo3conf/ext`.
This is a one way command with no way back. Don't blame anybody if this command destroys your data.
**Handle with care!**

This command is deprecated.
Instead of adding extensions and then removing them, just don't add them in the first place.



Options
~~~~~~~

`--force`
   The option has to be specified, otherwise nothing happens

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false






.. _`Command Reference: typo3_console extension:setup`:

`extension:setup`
-----------------

**Set up extension(s)**

Sets up one or more extensions by key.
Set up means:

- Database migrations and additions
- Importing files and data
- Writing default extension configuration

Arguments
~~~~~~~~~

`extensionKeys`
   Extension keys to set up. Separate multiple extension keys with comma.







.. _`Command Reference: typo3_console extension:setupactive`:

`extension:setupactive`
-----------------------

**Set up all active extensions**

Sets up all extensions that are marked as active in the system.

This command is especially useful for deployment, where extensions
are already marked as active, but have not been set up yet or might have changed. It ensures every necessary
setup step for the (changed) extensions is performed.
As an additional benefit no caches are flushed, which significantly improves performance of this command
and avoids unnecessary cache clearing.





Related commands
~~~~~~~~~~~~~~~~

`extension:setup`
  Set up extension(s)
`install:generatepackagestates`
  Generate PackageStates.php file
`cache:flush`
  Flush all caches



.. _`Command Reference: typo3_console frontend:request`:

`frontend:request`
------------------

**Submit frontend request**

Submits a frontend request to TYPO3 on the specified URL.

Arguments
~~~~~~~~~

`requestUrl`
   URL to make a frontend request.







.. _`Command Reference: typo3_console install:extensionsetupifpossible`:

`install:extensionsetupifpossible`
----------------------------------

**Setup TYPO3 with extensions if possible**

This command tries up all TYPO3 extensions, but quits gracefully if this is not possible.
This can be used in `composer.json` scripts to ensure that extensions
are always set up correctly after a composer run on development systems,
but does not fail on packaging for deployment where no database connection is available.

Besides that, it can be used for a first deploy of a TYPO3 instance in a new environment,
but also works for subsequent deployments.





Related commands
~~~~~~~~~~~~~~~~

`extension:setupactive`
  Set up all active extensions



.. _`Command Reference: typo3_console install:fixfolderstructure`:

`install:fixfolderstructure`
----------------------------

**Fix folder structure**

Automatically create files and folders, required for a TYPO3 installation.

This command creates the required folder structure needed for TYPO3 including extensions.
It is recommended to be executed **after** executing
`typo3cms install:generatepackagestates`, to ensure proper generation of
required folders for all active extensions.





Related commands
~~~~~~~~~~~~~~~~

`install:generatepackagestates`
  Generate PackageStates.php file



.. _`Command Reference: typo3_console install:generatepackagestates`:

`install:generatepackagestates`
-------------------------------

**Generate PackageStates.php file**

Generates and writes `typo3conf/PackageStates.php` file.
Goal is to not have this file in version control, but generate it on `composer install`.

Marks the following extensions as active:

- Third party extensions
- All core extensions that are required (or part of minimal usable system)
- All core extensions which are provided with the `--framework-extensions` argument.
- In composer mode all composer dependencies to TYPO3 framework extensions are detected and activated by default.

To require TYPO3 core extensions use the following command:

`composer require typo3/cms-foo "*"`

This updates your composer.json and composer.lock without any other changes.

**Example:** `typo3cms install:generatepackagestates`



Options
~~~~~~~

`--framework-extensions`
   TYPO3 system extensions that should be marked as active. Extension keys separated by comma.

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: array ()

`--excluded-extensions`
   Extensions which should stay inactive. This does not affect provided framework extensions or framework extensions that are required or part as minimal usable system.

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: array ()

`--activate-default`
   (DEPRECATED) If true, `typo3/cms` extensions that are marked as TYPO3 factory default, will be activated, even if not in the list of configured active framework extensions.

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false






.. _`Command Reference: typo3_console install:setup`:

`install:setup`
---------------

**TYPO3 Setup**

Use as command line replacement for the web installation process.
Manually enter details on the command line or non interactive for automated setups.
As an alternative for providing command line arguments, it is also possible to provide environment variables.
Command line arguments take precedence over environment variables.
The following environment variables are evaluated:

- TYPO3_INSTALL_DB_USER
- TYPO3_INSTALL_DB_PASSWORD
- TYPO3_INSTALL_DB_HOST
- TYPO3_INSTALL_DB_PORT
- TYPO3_INSTALL_DB_UNIX_SOCKET
- TYPO3_INSTALL_DB_USE_EXISTING
- TYPO3_INSTALL_DB_DBNAME
- TYPO3_INSTALL_ADMIN_USER
- TYPO3_INSTALL_ADMIN_PASSWORD
- TYPO3_INSTALL_SITE_NAME
- TYPO3_INSTALL_SITE_SETUP_TYPE
- TYPO3_INSTALL_WEB_SERVER_CONFIG



Options
~~~~~~~

`--force`
   Force installation of TYPO3, even if `LocalConfiguration.php` file already exists.

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false

`--skip-integrity-check`
   Skip the checking for clean state before executing setup. This allows a pre-defined `LocalConfiguration.php` to be present. Handle with care. It might lead to unexpected or broken installation results.

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false

`--skip-extension-setup`
   Skip setting up extensions after TYPO3 is set up. Defaults to false in composer setups and to true in non composer setups.

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false

`--install-steps-config`
   Override install steps with the ones given in this file

- Accept value: yes
- Is value required: yes
- Is multiple: no


`--database-user-name`
   User name for database server

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: ''

`--database-user-password`
   User password for database server

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: ''

`--database-host-name`
   Host name of database server

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: '127.0.0.1'

`--database-port`
   TCP Port of database server

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: '3306'

`--database-socket`
   Unix Socket to connect to (if localhost is given as hostname and this is kept empty, a socket connection will be established)

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: ''

`--database-name`
   Name of the database

- Accept value: yes
- Is value required: yes
- Is multiple: no


`--use-existing-database`
   If set an empty database with the specified name will be used. Otherwise a database with the specified name is created.

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false

`--admin-user-name`
   User name of the administrative backend user account to be created

- Accept value: yes
- Is value required: yes
- Is multiple: no


`--admin-password`
   Password of the administrative backend user account to be created

- Accept value: yes
- Is value required: yes
- Is multiple: no


`--site-name`
   Site Name

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: 'New TYPO3 Console site'

`--web-server-config`
   Web server config file to install in document root (`none`, `apache`, `iis`)

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: 'none'

`--site-setup-type`
   Can be either `no` (which unsurprisingly does nothing at all) or `site` (which creates an empty root page and setup)

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: 'no'

`--non-interactive`
   Deprecated. Use `--no-interaction` instead.

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false






.. _`Command Reference: typo3_console scheduler:run`:

`scheduler:run`
---------------

**Run scheduler**

Executes tasks that are registered in the scheduler module.

**Example:** `typo3cms scheduler:run 42 --force`



Options
~~~~~~~

`--task`
   Uid of the task that should be executed (instead of all scheduled tasks)

- Accept value: yes
- Is value required: yes
- Is multiple: no


`--force`
   The execution can be forced with this flag. The task will then be executed even if it is not scheduled for execution yet. Only works, when a task is specified.

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false

`--task-id`
   Deprecated option (same as --task)

- Accept value: yes
- Is value required: yes
- Is multiple: no







.. _`Command Reference: typo3_console upgrade:all`:

`upgrade:all`
-------------

**Execute all upgrade wizards that are scheduled for execution**





Options
~~~~~~~

`--arguments`
   Arguments for the wizard prefixed with the identifier, e.g. `compatibility7Extension[install]=0`; multiple arguments separated with comma

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: array ()






.. _`Command Reference: typo3_console upgrade:checkextensionconstraints`:

`upgrade:checkextensionconstraints`
-----------------------------------

**Check TYPO3 version constraints of extensions**

This command is especially useful **before** switching sources to a new TYPO3 version.
It checks the version constraints of all third party extensions against a given TYPO3 version.
It therefore relies on the constraints to be correct.

Arguments
~~~~~~~~~

`extensionKeys`
   Extension keys to check. Separate multiple extension keys with comma.



Options
~~~~~~~

`--typo3-version`
   TYPO3 version to check against. Defaults to current TYPO3 version.

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: '<Current-TYPO3-Version>'






.. _`Command Reference: typo3_console upgrade:list`:

`upgrade:list`
--------------

**List upgrade wizards**





Options
~~~~~~~

`--all`
   If set, all wizards will be listed, even the once marked as ready or done

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false






.. _`Command Reference: typo3_console upgrade:wizard`:

`upgrade:wizard`
----------------

**Execute a single upgrade wizard**



Arguments
~~~~~~~~~

`identifier`
   Identifier of the wizard that should be executed



Options
~~~~~~~

`--arguments`
   Arguments for the wizard prefixed with the identifier, e.g. `compatibility7Extension[install]=0`

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: array ()

`--force`
   Force execution, even if the wizard has been marked as done

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false






