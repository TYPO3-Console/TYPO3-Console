TYPO3 CMS Console [![Build Status](https://travis-ci.org/helhum/typo3_console.svg?branch=master)](https://travis-ci.org/helhum/typo3_console)
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
./typo3cms extension:activate realurl
```

## Features
* Command line tool
* TYPO3 installation from command line
* Flexible bootstrap for commands (not every command needs a fully bootstrapped framework)
* Reliable cache flush commands
* Scheduler command
* Backend lock/unlock commands
* Reference Index commands
* Many commands useful for deployment
* …
* Support for all other Extbase command controllers


## Installation

For the extension to work, it *must* be installed in the typo3conf/ext/ directory *not* in any other possible extension location.

Don't forget to activate the extension in the extension manager, which will copy the `typo3cms` command line tool
into the installation root directory..

### Linux and OS X Shell Installation

```
git clone https://github.com/helhum/typo3_console.git typo3conf/ext/typo3_console
php typo3/cli_dispatch.phpsh extbase extension:install typo3_console
php typo3cms help
```

## ToDo & Ideas

Currently only a few commands are delivered with the extension, but those delivered are quite useful already. And if any other extension
adds Extbase command controllers, they will also be usable with the typo3cms command.

* … Add ideas to the issues section of this repository where title is prepended with "idea:"
