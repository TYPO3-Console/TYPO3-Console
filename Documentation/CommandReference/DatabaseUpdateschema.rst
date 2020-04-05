
.. include:: ../Includes.txt



.. _typo3_console-command-reference-database-updateschema:

The following reference was automatically generated from code.


=====================
database:updateschema
=====================


**Update database schema (TYPO3 Database Compare)**

Compares the current database schema with schema definition
from extensions's ext_tables.sql files and updates the schema based on the definition.

Valid schema update types are:

- field.add
- field.change
- field.prefix
- field.drop
- table.add
- table.change
- table.prefix
- table.drop
- safe (includes all necessary operations, to add or change fields or tables)
- destructive (includes all operations which rename or drop fields or tables)

The list of schema update types supports wildcards to specify multiple types, e.g.:

- "`*`" (all updates)
- "`field.*`" (all field updates)
- "`*.add,*.change`" (all add/change updates)

To avoid shell matching all types with wildcards should be quoted.

**Example:**

  `typo3cms database:updateschema "*.add,*.change"`

Arguments
~~~~~~~~~

`schemaUpdateTypes`
   List of schema update types (default: "safe")



Options
~~~~~~~

`--dry-run`
   If set the updates are only collected and shown, but not executed

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false





