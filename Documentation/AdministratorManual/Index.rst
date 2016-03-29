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

There are **three** ways to properly install the extension.

1. Installation with Extension Manager
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

For the extension to work, it **must** be installed in the ``typo3conf/ext/`` directory **not** in any other possible extension location.
This is the default location when downloading it from TER with the Extension Manager.

The extension will automatically be activated and the ``typo3cms`` script will also copied to your TYPO3 root directory.


2. Clone the repository
^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: bash

	git clone https://github.com/helhum/typo3_console.git typo3conf/ext/typo3_console

Don't forget to **activate the extension** in the extension manager. Once you active the extension
the ``typo3cms`` script is automatically copied to your TYPO3 root directory.

3. Via composer
^^^^^^^^^^^^^^^

Create a root ``composer.json`` file like this add the missing lines to your existing ``composer.json`` file and run `composer update` or `composer install` respectively ::

	{
		"repositories": [
			{ "type": "composer", "url": "https://composer.typo3.org/" }
		],
		"name": "typo3/cms-example-distribution",
		"description" : "TYPO3 CMS Example Distribution",
		"license": "GPL-2.0+",
		"scripts": {
			"post-autoload-dump": "Helhum\\Typo3Console\\Composer\\InstallerScripts::setupConsole"
		},
		"require": {
			"typo3/cms": "^7.6.6",
			"helhum/typo3-console": "^1.3.0"
		}
	}

Don't forget to **activate the extension** in the extension manager.

The ``typo3cms`` script will automatically be copied to your TYPO3 root directory.

