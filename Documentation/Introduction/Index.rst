.. include:: /Includes.rst.txt
.. highlight:: shell

============
Introduction
============

What does it do?
================

The goal of this extension is to improve the command line usage with TYPO3 CMS.
Every command that is shipped provides helpful information about usage and
aims to be easy to understand and use.

TYPO3 Console provides some commands, that currently aren't available in TYPO3::

   # Interactively set up a new TYPO3 instance from command line (instead of
   # using the install tool) typo3 install:setup

   # Non interactive automatic setup of a new TYPO3 instance
   typo3 install:setup --no-interaction \
      --database-user-name="root" \
      --database-host-name="localhost" \
      --database-port="3306" \
      --database-name="travis_test" \
      --admin-user-name="admin" \
      --admin-password="password" \
      --site-name="Travis Install"

   # Perform safe database schema updates
   typo3 database:updateschema "*.add,*.change"

A help system is integrated, so that you can easily list all available commands
or get help for individual commands::

   # List all commands with a short description
   typo3 help

   # Show detailed help for an individual command
   typo3 help database:updateschema
