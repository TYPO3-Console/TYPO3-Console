
.. include:: /Includes.rst.txt

.. The following reference was automatically generated from code. It should not
.. be changed directly.

.. _typo3_console-command-reference-install-setup:

=============
install:setup
=============


**TYPO3 Setup**

Use as command line replacement for the web installation process.
Manually enter details on the command line or non interactive for automated setups.
As an alternative for providing command line arguments, it is also possible to provide environment variables.
Command line arguments take precedence over environment variables.
The following environment variables are evaluated:

- TYPO3_INSTALL_DB_DRIVER
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
- TYPO3_INSTALL_SITE_BASE_URL
- TYPO3_INSTALL_WEB_SERVER_CONFIG



Options
=======

`--force|-f`
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


`--database-driver`
   Database connection type (one of mysqli, pdo_sqlite, pdo_mysql, pdo_pgsql, mssql) Note: pdo_sqlite is only supported with TYPO3 9.5 or higher

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: 'mysqli'

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

`--site-base-url`
   When `site-setup-type` is set to `site`, this base url is used for the created site configuration

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: '/'





