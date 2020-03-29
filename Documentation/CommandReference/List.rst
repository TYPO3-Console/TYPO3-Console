
.. include:: ../Includes.txt



.. _typo3_console-command-reference-list:

The following reference was automatically generated from code.


====
list
====


**Lists commands**

The `list` command lists all commands:

  `php typo3cms list`

You can also display the commands for a specific namespace:

  `php typo3cms list test`

You can also output the information in other formats by using the **--format** option:

  `php typo3cms list --format=xml`

It's also possible to get raw list of commands (useful for embedding command runner):

  `php typo3cms list --raw`

Arguments
~~~~~~~~~

`namespace`
   The namespace name



Options
~~~~~~~

`--raw`
   To output raw command list

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false

`--format`
   The output format (txt, xml, json, or md)

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: 'txt'

`--all|-a`
   Show all commands, even the ones not available

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false





