[![Latest Stable Version](https://poser.pugx.org/helhum/typo3-console/v/stable.svg)](https://packagist.org/packages/helhum/typo3-console)
[![TYPO3 11](https://img.shields.io/badge/TYPO3-11-orange.svg?style=flat-square)](https://get.typo3.org/version/11)
[![TYPO3 12](https://img.shields.io/badge/TYPO3-12-orange.svg?style=flat-square)](https://get.typo3.org/version/12)
[![TYPO3 13](https://img.shields.io/badge/TYPO3-13-orange.svg?style=flat-square)](https://get.typo3.org/version/13)
[![Total Downloads](https://poser.pugx.org/helhum/typo3-console/downloads.svg)](https://packagist.org/packages/helhum/typo3-console)
[![Monthly Downloads](https://poser.pugx.org/helhum/typo3-console/d/monthly)](https://packagist.org/packages/helhum/typo3-console)
[![Build Status](https://github.com/TYPO3-Console/TYPO3-Console/actions/workflows/Test.yml/badge.svg?branch=main)](https://github.com/TYPO3-Console/TYPO3-Console/actions/workflows/Test.yml)
[![StyleCI](https://styleci.io/repos/19455482/shield?branch=main)](https://styleci.io/repos/19455482)
[![License](https://poser.pugx.org/helhum/typo3-console/license)](https://packagist.org/packages/helhum/typo3-console)
[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.me/helhum/19.99)

# TYPO3 Console

TYPO3 Console (`helhum/typo3-console`) provides a clean way to register commands and
a sane way to call these commands through the TYPO3 core command line tool called `typo3`.

### Important information for longtime users
**The CLI binary `typo3cms` was removed with version 8.0.0 and is replaced by the TYPO3 CLI binary `typo3`.
Commands that were called e.g. `typo3cms database:updateschema` before,
must now be called with `typo3 database:updateschema`.**

It ships many commands to execute TYPO3 actions, which otherwise would only be accessible via the TYPO3 backend.
This makes TYPO3 Console a perfect companion for development, deployment, Docker setups, continuous integration
workflows or anything else where automation is required or beneficial.

Examples for such commands are:

* `typo3 install:setup` to completely set up TYPO3 from command line
* `typo3 database:updateschema` to perform granular database schema updates

|                  | URL                                                       |
|------------------|-----------------------------------------------------------|
| **Repository:**  | https://github.com/TYPO3-Console/TYPO3-Console            |
| **Read online:** | https://docs.typo3.org/p/helhum/typo3-console/main/en-us/ |
| **TER:**         | https://extensions.typo3.org/extension/typo3_console      |
