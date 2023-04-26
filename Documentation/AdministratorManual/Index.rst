.. include:: /Includes.rst.txt
.. highlight:: shell


.. _admin-manual:


======================
Administrator's Manual
======================


Describes how to manage the extension from an administrator’s point of view.
That relates to Page/User TSconfig, permissions, configuration etc., which
administrator level users have access to.

Language should be non/semi-technical, explaining, using small examples.

Target group: **Administrators**, **Developers**



Installation
============

There are two ways to properly install the extension. Using git to clone the
the repository is deprecated and most likely will not work any more in the near
future.


1. Composer installation
------------------------

In case you use Composer to manage dependencies of your TYPO3 project, you can
just issue the following Composer command in your project root directory::

   composer require helhum/typo3-console

In case you are unsure how to create a Composer based TYPO3 project, you can
check out this `TYPO3 distribution
<https://github.com/helhum/TYPO3-Distribution>`_, which already provides TYPO3
Console integration.


2. Non Composer installation
----------------------------

For the extension to work, it **must** be installed in the
directory **not** in any other possible extension location. This is the default
location when downloading it from TER with the Extension Manager.

In order for it to work properly in not installed TYPO3 (extracted sources,
extension is placed in `typo3conf/ext/`), the following binary must be executed:

`typo3conf/ext/typo3_console/activate`

It is a PHP binary like the `typo3` binary, thus, if you need a dedicated PHP binary
to be used, put it in front like so: `/path/to/php typo3conf/ext/typo3_console/activate`

When your TYPO3 installation is already set up, use `typo3 extension:activate typo3_console`,
to activate the extension and get all commands from it.


Shell auto complete
===================

You can get shell auto completion by using the great `autocomplete package
<https://github.com/bamarni/symfony-console-autocomplete>`_.
Install the package and make the binary available in your path. Please read the
installation instructions of this package on how to do that.

To temporary activate auto complete in the current shell session, type `eval
"$(symfony-autocomplete --aliases=typo3)"` You can also put this into your
`.profile` or `.bashrc` file to have it always available. Auto completion is
then always dynamic and reflects the commands you have available in your TYPO3
installation.

