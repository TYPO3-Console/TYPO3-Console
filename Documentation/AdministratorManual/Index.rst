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

The ``typo3cms`` binary will be installed by Composer in the specified bin-dir (by default ``vendor/bin``).

In case you are unsure how to create a Composer based TYPO3 project, you can check out
this `TYPO3 distribution`_, which already provides TYPO3 Console integration.

2. Installation with Extension Manager
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

For the extension to work, it **must** be installed in the ``typo3conf/ext/`` directory **not** in any other possible extension location.
This is the default location when downloading it from TER with the Extension Manager.

The extension will automatically be activated and the ``typo3cms`` script will also copied to your TYPO3 root directory.

.. _`TYPO3 distribution`: https://github.com/helhum/TYPO3-Distribution


Shell auto complete
-------------------

You can get shell auto completion either for bash or zsh shells.

1. Static auto complete
^^^^^^^^^^^^^^^^^^^^^^^

To generate an auto completion shell script, which includes all commands and command options of the current
TYPO3 installation, just type ``/path/to/typo3cms autocomplete bash`` (or ``/path/to/typo3cms autocomplete zsh`` for zsh shell).

To temporary activate auto complete in the current shell session, type ``eval "$(/path/to/typo3cms autocomplete bash)"`` or ``eval "$(/path/to/typo3cms autocomplete zsh)"``.
If you only have one TYPO3 installation on a system, you can also put the above into your ``.profile`` file,
so that the auto completion is active upon login.

.. note::

  The ``typo3cms`` command may show commands that are specific to the current TYPO3 installation.
  However the static auto completion script that is generated contains only the commands of the TYPO3 installation,
  for which it was issued. This means if you permanently install the script
  generated for one TYPO3 installation, you may get unexpected results in another one.

2. Dynamic auto complete
^^^^^^^^^^^^^^^^^^^^^^^^

To generate an auto completion shell script, which extract the commands and option to be auto completed
dynamically from the current TYPO3 installation, type ``/path/to/typo3cms autocomplete bash --dynamic`` (or ``/path/to/typo3cms autocomplete zsh --dynamic`` for zsh shell).

To temporary activate auto complete in the current shell session, type ``eval "$(/path/to/typo3cms autocomplete bash --dynamic)"`` or ``eval "$(/path/to/typo3cms autocomplete zsh --dynamic)"``.
You can also put the above into your ``.profile`` file, so that the auto completion is active upon login.

This auto completion type is more flexible, but completion takes a little bit longer, as
the list of completion possibilities is extracted dynamically upon invocation of the auto complete request (tab pressed).


.. note::

  Whether to use static or dynamic auto completion is up to you. Static is much faster,
  so choose this, if performance is king for you. Dynamic is slower, but you don't have
  to worry about different commands in different TYPO3 installations.
