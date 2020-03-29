
.. include:: ../Includes.txt



.. _typo3_console-command-reference-documentation-generatexsd:

The following reference was automatically generated from code.


=========================
documentation:generatexsd
=========================


**Generate Fluid ViewHelper XSD Schema**

Generates Schema documentation (XSD) for your ViewHelpers, preparing the
file to be placed online and used by any XSD-aware editor.
After creating the XSD file, reference it in your IDE and import the namespace
in your Fluid template by adding the xmlns:* attribute(s):
`<html xmlns="http://www.w3.org/1999/xhtml" xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers" ...>`

Arguments
~~~~~~~~~

`phpNamespace`
   Namespace of the Fluid ViewHelpers without leading backslash (for example "TYPO3\Fluid\ViewHelpers" or "Tx_News_ViewHelpers"). NOTE: Quote and/or escape this argument as needed to avoid backslashes from being interpreted!



Options
~~~~~~~

`--xsd-namespace|-x`
   Unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers"). Defaults to "http://typo3.org/ns/<php namespace>".

- Accept value: yes
- Is value required: yes
- Is multiple: no


`--target-file|-t`
   File path and name of the generated XSD schema. If not specified the schema will be output to standard output.

- Accept value: yes
- Is value required: yes
- Is multiple: no






