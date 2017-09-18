TYPO3 Console
=================

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.me/helhum/19.99)
[![Build Status](https://travis-ci.org/TYPO3-Console/TYPO3-Console.svg?branch=master)](https://travis-ci.org/TYPO3-Console/TYPO3-Console)
[![StyleCI](https://styleci.io/repos/19455482/shield?branch=master)](https://styleci.io/repos/19455482)
[![Latest Stable Version](https://poser.pugx.org/helhum/typo3-console/v/stable.svg)](https://packagist.org/packages/helhum/typo3-console)
[![Total Downloads](https://poser.pugx.org/helhum/typo3-console/downloads.svg)](https://packagist.org/packages/helhum/typo3-console)

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
typo3cms extension:activate realurl
```

Notice that the location of `typo3cms` depends on your installation type, see *Installation* below.

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

### Installation using Composer

The recommended way to install TYPO3 Console is by using [Composer](https://getcomposer.org).
In your Composer based TYPO3 project root, just do `composer require helhum/typo3-console`.
The `typo3cms` binary will be installed by Composer in the specified bin-dir (by default `vendor/bin`).
TYPO3 Console is a perfect companion for Composer based, enjoyable [TYPO3 projects](https://github.com/helhum/TYPO3-Distribution).

### Installation as extension from TYPO3 Extension Repository (TER)

Download and install the extension with the extension manager module.
For the extension to work, it **must** be installed in the typo3conf/ext/ directory **not** in any other possible extension location.
This directory **must not** be a symlink to another location!
The extension manager, will copy the `typo3cms` command line tool
into the installation root directory during activation.

## Submit bug reports or feature requests

Look at the [Issues](https://github.com/TYPO3-Console/typo3_console/issues)
for what has been planned to be implemented in the (near) future.
