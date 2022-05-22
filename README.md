[![Latest Stable Version](https://poser.pugx.org/helhum/typo3-console/v/stable.svg)](https://packagist.org/packages/helhum/typo3-console)
[![TYPO3 11](https://img.shields.io/badge/TYPO3-11-orange.svg?style=flat-square)](https://get.typo3.org/version/11)
[![Total Downloads](https://poser.pugx.org/helhum/typo3-console/downloads.svg)](https://packagist.org/packages/helhum/typo3-console)
[![Monthly Downloads](https://poser.pugx.org/helhum/typo3-console/d/monthly)](https://packagist.org/packages/helhum/typo3-console)
[![Build Status](https://github.com/TYPO3-Console/TYPO3-Console/actions/workflows/Test.yml/badge.svg?branch=main)](https://github.com/TYPO3-Console/TYPO3-Console/actions/workflows/Test.yml)
[![StyleCI](https://styleci.io/repos/19455482/shield?branch=main)](https://styleci.io/repos/19455482)
[![License](https://poser.pugx.org/helhum/typo3-console/license)](https://packagist.org/packages/helhum/typo3-console)
[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.me/helhum/19.99)

# TYPO3 Console

TYPO3 Console (helhum/typo3-console) provides a clean way to register commands and
a sane way to call these commands through a command line tool called `typo3cms`.

It ships many commands to execute TYPO3 actions, which otherwise would only be accessible via the TYPO3 backend.
This makes TYPO3 Console a perfect companion for development, deployment, Docker setups, continuous integration
workflows or anything else where automation is required or beneficial.

Examples for such commands are:

* `typo3cms install:setup` to completely set up TYPO3 from command line
* `typo3cms upgrade:run` to execute upgrades from command line
* `typo3cms extension:setupactive` to set up all active extensions (database schema update, data import, …)

and the features include:

* TYPO3 installation and upgrades from command line
* Flexible bootstrap for commands (not every command needs a fully bootstrapped framework)
* Reliable cache flush commands
* Many commands useful for deployment
* Support for Symfony commands registered within TYPO3 extensions and Composer packages

|                  | URL                                                       |
|------------------|-----------------------------------------------------------|
| **Repository:**  | https://github.com/TYPO3-Console/TYPO3-Console            |
| **Read online:** | https://docs.typo3.org/p/helhum/typo3-console/main/en-us/ |
| **TER:**         | https://extensions.typo3.org/extension/typo3_console      |
