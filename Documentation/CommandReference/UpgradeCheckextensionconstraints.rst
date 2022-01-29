
.. include:: /Includes.rst.txt

.. The following reference was automatically generated from code. It should not
.. be changed directly.

.. _typo3_console-command-reference-upgrade-checkextensionconstraints:

=================================
upgrade:checkextensionconstraints
=================================


**Check TYPO3 version constraints of extensions**

This command is especially useful **before** switching sources to a new TYPO3 version.
It checks the version constraints of all third party extensions against a given TYPO3 version.
It therefore relies on the constraints to be correct.

Arguments
=========

`extensionKeys`
   Extension keys to check. Separate multiple extension keys with comma



Options
=======

`--typo3-version`
   TYPO3 version to check against. Defaults to current TYPO3 version

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: '<Current-TYPO3-Version>'





