
.. include:: /Includes.rst.txt

.. The following reference was automatically generated from code. It should not
.. be changed directly.

.. _typo3_console-command-reference-install-extensionsetupifpossible:

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



Options
=======

`--fail-on-error`
   Instead of gracefully exiting this command if something goes wrong, throw an error

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false





