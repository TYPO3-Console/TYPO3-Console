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

In case you use Composer to manage dependencies of your TYPO3 project,
you can just issue the following Composer command in your project root directory.

.. code-block:: bash

	composer require helhum/typo3-console

The ``typo3console`` binary will be installed by Composer in the specified bin-dir (by default ``vendor/bin``).

In case you are unsure how to create a Composer based TYPO3 project, you can check out
this `TYPO3 distribution`_, which already provides TYPO3 Console integration.

2. Installation with Extension Manager
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

For the extension to work, it **must** be installed in the ``typo3conf/ext/`` directory **not** in any other possible extension location.
This is the default location when downloading it from TER with the Extension Manager.

The ``typo3console`` script will be copied to your TYPO3 root directory, when you activate it.
When you symlink the ``typo3console`` script to a location of your preference, TYPO3 Console
will work, even when it is not marked as active in the Extension Manager.

.. _`TYPO3 distribution`: https://github.com/helhum/TYPO3-Distribution


Shell auto complete
-------------------

You can get shell auto completion by using the great `autocomplete package`_.
Install the package and make the binary available in your path. Please read the installation instructions
of this package on how to do that.

To temporary activate auto complete in the current shell session, type ``eval "$(symfony-autocomplete --aliases=typo3console)"``
You can also put this into your ``.profile`` or ``.bashrc`` file to have it always available.
Auto completion is then always dynamic and reflects the commands you have available in your TYPO3 installation.

.. _`autocomplete package`: https://github.com/bamarni/symfony-console-autocomplete
