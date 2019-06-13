# TYPO3 Console

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.me/helhum/19.99)
[![Build Status](https://travis-ci.org/TYPO3-Console/TYPO3-Console.svg?branch=master)](https://travis-ci.org/TYPO3-Console/TYPO3-Console)
[![Build Status (Windows)](https://ci.appveyor.com/api/projects/status/github/TYPO3-Console/TYPO3-Console?branch=master&svg=true)](https://ci.appveyor.com/project/helhum/typo3-console-qpjaf/branch/master)
[![StyleCI](https://styleci.io/repos/19455482/shield?branch=master)](https://styleci.io/repos/19455482)
[![Maintainability](https://api.codeclimate.com/v1/badges/03aa352b8c4c20e06639/maintainability)](https://codeclimate.com/github/TYPO3-Console/TYPO3-Console/maintainability)
[![Latest Stable Version](https://poser.pugx.org/helhum/typo3-console/v/stable.svg)](https://packagist.org/packages/helhum/typo3-console)
[![Monthly Downloads](https://poser.pugx.org/helhum/typo3-console/d/monthly)](https://packagist.org/packages/helhum/typo3-console)
[![Total Downloads](https://poser.pugx.org/helhum/typo3-console/downloads.svg)](https://packagist.org/packages/helhum/typo3-console)
[![License](https://poser.pugx.org/helhum/typo3-console/license)](https://packagist.org/packages/helhum/typo3-console)

TYPO3 Console provides a clean way to register commands and
a sane way to call these commands through a command line tool called `typo3cms`.

It ships many commands to execute TYPO3 actions, which otherwise would only be accessible via the TYPO3 backend. This makes TYPO3 Console a perfect companion for development, deployment, Docker setups, continuous integration workflows or anything else where automation is required or beneficial.

Examples for such commands are:

* `typo3cms install:setup` to completely set up TYPO3 from command line
* `typo3cms upgrade:all` to execute upgrades from command line
* `typo3cms extension:setupactive` to set up all active extensions (database schema update, data import, …)

## Features
* TYPO3 installation and upgrades from command line
* Flexible bootstrap for commands (not every command needs a fully bootstrapped framework)
* Reliable cache flush commands
* Many commands useful for deployment
* Support for command controllers and Symfony commands registered within TYPO3 extensions and Composer packages

## Installation

### Installation using Composer

The recommended way to install TYPO3 Console is by using [Composer](https://getcomposer.org):

    composer require helhum/typo3-console

The `typo3cms` binary will be installed by Composer in the specified `bin-dir` (by default `vendor/bin`).
TYPO3 Console is a perfect companion for Composer-based, enjoyable [TYPO3 projects](https://github.com/helhum/TYPO3-Distribution).

### Installation as extension from TYPO3 Extension Repository (TER)

Download and install the extension with the *Extensions* module.
For the extension to work, it **must** be installed in the `typo3conf/ext` directory **not** in any other extension location.
This directory **must not** be a symlink to another location!
The `typo3cms` command line tool will be copied into the installation root directory during activation,
in case it is not present as a symlink to `typo3conf/ext/typo3_console/Libraries/helhum/typo3-console/typo3cms`.
The build extension is automatically published to [TER](https://extensions.typo3.org/extension/typo3_console/)
and to a [read only repository](https://github.com/TYPO3-Console/Extension) representing the released state as tags,
as well as current state of development in the respective branches.

## Submit bug reports or feature requests

Look at the [Issues](https://github.com/TYPO3-Console/typo3_console/issues)
for what has been planned to be implemented in the (near) future.

## Credits
Thanks to all contributors and everybody providing feedback.

Special thanks to [@hzoo](https://github.com/hzoo) for creating great Github issue templates for [Babel](https://github.com/babel/babel),
which were adopted for this package.
