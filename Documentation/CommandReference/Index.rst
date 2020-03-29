
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

.. toctree::
   :maxdepth: 5
   :titlesonly:


   Dumpautoload

   Help

   List

   BackendCreateadmin

   BackendLock

   BackendLockforeditors

   BackendUnlock

   BackendUnlockforeditors

   CacheFlush

   CacheFlushgroups

   CacheFlushtags

   CacheListgroups

   CleanupUpdatereferenceindex

   ConfigurationRemove

   ConfigurationSet

   ConfigurationShow

   ConfigurationShowactive

   ConfigurationShowlocal

   DatabaseExport

   DatabaseImport

   DatabaseUpdateschema

   DocumentationGeneratexsd

   ExtensionActivate

   ExtensionDeactivate

   ExtensionList

   ExtensionRemoveinactive

   ExtensionSetup

   ExtensionSetupactive

   FrontendRequest

   InstallExtensionsetupifpossible

   InstallFixfolderstructure

   InstallGeneratepackagestates

   InstallSetup

   SchedulerRun

   UpgradeAll

   UpgradeCheckextensionconstraints

   UpgradeList

   UpgradeWizard

