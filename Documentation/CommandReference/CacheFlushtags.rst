
.. include:: /Includes.rst.txt

.. The following reference was automatically generated from code. It should not
.. be changed directly.

.. _typo3_console-command-reference-cache-flushtags:

===============
cache:flushtags
===============


**Flush cache by tags**

Flushes caches by tags, optionally only caches in specified groups.

**Example:**


.. code-block:: shell

   typo3cms cache:flushtags news_123 --groups pages,all


Arguments
=========

`tags`
   Array of tags (specified as comma separated values) to flush.



Options
=======

`--groups`
   Optional array of groups (specified as comma separated values) for which to flush tags. If no group is specified, caches of all groups are flushed.

- Accept value: yes
- Is value required: yes
- Is multiple: no






