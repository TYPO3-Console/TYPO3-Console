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

	git clone https://github.com/TYPO3-Console/typo3_console.git typo3conf/ext/typo3_console
	ln -s typo3conf/ext/typo3_console/Scripts/typo3cms typo3cms
	composer create-libs --working-dir=typo3conf/ext/typo3_console

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
		"require": {
			"typo3/cms": "^7.6.6",
			"helhum/typo3-console": "^3.0.0"
		}
	}


The ``typo3cms`` script will automatically be linked to your TYPO3 root directory and the extension will activate itself automatically as well.

