
.. include:: /Includes.rst.txt

.. The following reference was automatically generated from code. It should not
.. be changed directly.

.. _typo3_console-command-reference-cache-flushtags:

===============
cache:flushtags
===============


**Flush TYPO3 caches with tags.**

This command can be used to clear the caches with specific tags, for example after code updates in local development and after deployments.

Arguments
=========

`tags`
   Array of tags (specified as comma separated values) to flush.



Options
=======

`--groups|-g`
   Array of groups (specified as comma separated values) for which to flush tags. If no group is specified, caches of all groups are flushed.

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: 'all'





