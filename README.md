TYPO3 CMS Console
=================

Many frameworks come with command helper tools that enables interactions on the command line easily.
TYPO3 CMS currently has CLI support, but no dedicated command tool but only a CLI dispatcher script
which not straight forward to use and not nice to extend for developers.

A great step forward for developers is the possibility to register command controllers, but running them
also requires calling the cli_dispatcher. Another downside is, that until finally the command controller is reached,
the framework jumps through several hoops to finally bootstrap Extbase and run the requested command.

The goal of this project is to provide a clean API to register commands (using Extbase Command Controllers) and
providing a sane way to call the commands through a single command line tool called "typo3cms"

e.g.

Instead of typing

```
./typo3/cli_dispatch.phpsh extbase extension:install realurl
```

just type:

```
./typo3cms extension:install realurl
```

**Features**
* Command line tool
* Flexible bootstrap for commands (not every command needs a fully bootstrapped framework)
* Cache flush and core cache warmup commands
* Scheduler command
* Backend lock/unlock commands
* Reference Index commands
* Support for all other Extbase command controllers


**Installation**

For the extension to work, it *must* be installed in the typo3conf/ext/ directory *not* in any other possible extension location.
To get the typo3cms command, just _copy_ typo3_console/Scripts/typo3cms to your TYPO3 root directory. Windows users must copy
typo3_console/Scripts/typo3cms.bat to the TYPO3 root directory and set the location to the php.exe in that file (untested but should work).

**TODO**

Currently only a few commands are delivered with the extension, but those delivered are quite useful already. And if any other extension
adds Extbase command controllers, they will also be useable with the typo3cms command.

* convert lowlevel_cleaner to command controller
* add basic package/extension kickstarting commands
* add commands to upload extensions to TER (with user interaction for password)
* add possibility to install and configure TYPO3 (database migrations, changing configuration etc.)
* add possibility to update and upgrade TYPO3
* … contact me if you have further ideas
