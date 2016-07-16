.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _admin-manual:

Administrator Manual
====================

Describes how to manage the extension from an administrator’s point of
view. That relates to Page/User TSconfig, permissions, configuration
etc., which administrator level users have access to.

Language should be non/semi-technical, explaining, using small
examples.

Target group: **Administrators**, **Developers**


Installation
------------

There are two ways to properly install the extension. Using git to clone the
the repository is deprecated and most likely will not work any more in the near future.

1. Composer installation
^^^^^^^^^^^^^^^^^^^^^^^^

In case you use composer to manage dependencies of your TYPO3 project,
you can just issue the following composer command in your project root directory.

.. code-block:: bash

	composer require helhum/typo3-console

The ``typo3cms`` binary will be installed by composer in the specified bin-dir (by default ``vendor/bin``).

In case you are unsure how to create a composer based TYPO3 project, you can check out
this `TYPO3 distribution`_, which already provides TYPO3 Console integration.

2. Installation with Extension Manager
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

For the extension to work, it **must** be installed in the ``typo3conf/ext/`` directory **not** in any other possible extension location.
This is the default location when downloading it from TER with the Extension Manager.

The extension will automatically be activated and the ``typo3cms`` script will also copied to your TYPO3 root directory.


.. _`TYPO3 distribution`: https://github.com/helhum/TYPO3-Distribution
