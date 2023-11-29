
.. include:: /Includes.rst.txt

.. _typo3_console-command-reference:

=================
Command Reference
=================

.. note::

   This reference uses `typo3` as the command to invoke. If you are on
   Windows, this will probably not work, there you need to use `typo3.bat`
   instead. In Composer based installations, the `typo3` binary will be
   located in the binary directory specified in the root composer.json (by
   default `vendor/bin`)

The following reference was automatically generated from code.

Application Options
-------------------

The following options can be used with every command:

`--help|-h`
   Display help for the given command. When no command is given display help for the `list` command

`--quiet|-q`
   Do not output any message

`--verbose|-v|-vv|-vvv`
   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

`--version|-V`
   Display this application version

`--ansi`
   Force (or disable --no-ansi) ANSI output

`--no-interaction|-n`
   Do not ask any interactive question



.. _`Command Reference: typo3_console`:

Available Commands
------------------

.. toctree::
   :maxdepth: 5
   :titlesonly:


   BackendCreateadmin

   BackendLockforeditors

   BackendUnlockforeditors

   CacheFlushtags

   CacheListgroups

   ConfigurationShowactive

   ConfigurationShow

   FrontendRequest

   ConfigurationRemove

   ConfigurationSet

   ConfigurationShowlocal

   DatabaseExport

   DatabaseImport

   DatabaseUpdateschema

   InstallSetup

   InstallFixfolderstructure

   InstallExtensionsetupifpossible

   InstallLock

   InstallUnlock

   FrontendAsseturl

