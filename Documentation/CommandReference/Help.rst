
.. include:: ../Includes.txt



.. _typo3_console-command-reference-help:

The following reference was automatically generated from code.


====
help
====


**Displays help for a command**

The `help` command displays help for a given command:

  `php typo3cms help list`

You can also output the help in other formats by using the **--format** option:

  `php typo3cms help --format=xml list`

To display the list of available commands, please use the `list` command.

Arguments
~~~~~~~~~

`command_name`
   The command name



Options
~~~~~~~

`--format`
   The output format (txt, xml, json, or md)

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: 'txt'

`--raw`
   To output raw command help

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false





