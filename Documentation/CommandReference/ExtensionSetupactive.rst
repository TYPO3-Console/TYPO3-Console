
.. include:: ../Includes.txt



.. _typo3_console-command-reference-extension-setupactive:

The following reference was automatically generated from code.


=====================
extension:setupactive
=====================


**Set up all active extensions**

Sets up all extensions that are marked as active in the system.

This command is especially useful for deployment, where extensions
are already marked as active, but have not been set up yet or might have changed. It ensures every necessary
setup step for the (changed) extensions is performed.
As an additional benefit no caches are flushed, which significantly improves performance of this command
and avoids unnecessary cache clearing.





Related commands
~~~~~~~~~~~~~~~~

`extension:setup`
  Set up extension(s)
`install:generatepackagestates`
  Generate PackageStates.php file
`cache:flush`
  Flush all caches


