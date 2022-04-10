.. include:: /Includes.rst.txt
.. highlight:: shell

============
Introduction
============

What does it do?
================

The goal of this extension is to improve the command line usage with TYPO3 CMS
inspired by the command line interface of TYPO3 Flow. It aims to provide a
consistent and easy to use interface for users and an easy API for developers.
Every command that is shipped provides helpful information about usage and is
easy to understand and use.

TYPO3 CMS already has a command line interface but executing commands can look
like this::

   # Run reference index check
   ./typo3/cli_dispatch.phpsh lowlevel_refindex -c

   # Update translated labels
   ./typo3/cli_dispatch.phpsh extbase language:update

   # Lock editing in the backend
   ./cli_dispatch.phpsh lowlevel_admin setBElock


This extension comes with a command line tool called `typo3cms` and you can
execute the commands from above like this::

   # Run reference index check
   typo3cms cleanup:updatereferenceindex --dry-run

   # Update translated labels
   typo3cms language:update

   # Lock editing in the backend
   typo3cms backend:lock


Additionally it provides some commands, that wouldn't be possible at all with
the current core command line interface::

   # Force flush all caches in a reliable and robust way
   typo3cms cache:flush --force

   # Interactively set up a new TYPO3 instance from command line (instead of
   # using the install tool) typo3cms install:setup

   # Non interactive automatic setup of a new TYPO3 instance
   typo3cms install:setup --no-interaction \
      --database-user-name="root" \
      --database-host-name="localhost" \
      --database-port="3306" \
      --database-name="travis_test" \
      --admin-user-name="admin" \
      --admin-password="password" \
      --site-name="Travis Install"

   # Perform safe database schema updates
   typo3cms database:updateschema "*.add,*.change"

A help system is integrated, so that you can easily list all available commands
or get help for individual commands::

   # List all commands with a short description
   typo3cms help

   # Show detailed help for an individual command
   typo3cms help cache:flush


FAQ
===

**Q:** How does `typo3_console` compare to `coreapi`?
-----------------------------------------------------

**A:** There is a `blog post
<http://insight.helhum.io/post/104528981610/about-the-beauty-and-power-of-typo3console>`__
that explains the differences and points out the benefits of `typo3_console`.


**Q:** Why isn't this functionality part of the TYPO3 core?
-----------------------------------------------------------

Wouldn't it make sense?

**A:** Absolutely! Only, nobody did this until now. I also think that it is
good to develop and stabilize this as third party product. It is possible to
get feedback, iterate and improve much faster than with a TYPO3 CMS core
release. Once the extension has matured, I'm fine to integrate it as core
functionality.


**Q:** When will this be part of the TYPO3 CMS core?
----------------------------------------------------

**A:** It might find its way into the next TYPO3 LTS version, who knows. Until
then you can already use it as extension for all current LTS versions.

