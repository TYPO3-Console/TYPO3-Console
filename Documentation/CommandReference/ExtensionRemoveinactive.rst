
.. include:: ../Includes.txt



.. _typo3_console-command-reference-extension-removeinactive:

The following reference was automatically generated from code.


========================
extension:removeinactive
========================


**Removes all extensions that are not marked as active**

Directories of inactive extension are **removed** from `typo3/sysext` and `typo3conf/ext`.
This is a one way command with no way back. Don't blame anybody if this command destroys your data.
**Handle with care!**

**This command is deprecated.**

  Instead of adding extensions and then removing them, just don't add them in the first place.



Options
~~~~~~~

`--force|-f`
   The option has to be specified, otherwise nothing happens

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false





