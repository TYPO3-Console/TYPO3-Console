
.. include:: ../Includes.txt



.. _typo3_console-command-reference-install-extensionsetupifpossible:

The following reference was automatically generated from code.


================================
install:extensionsetupifpossible
================================


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


