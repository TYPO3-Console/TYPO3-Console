<?php
namespace Helhum\Typo3Console\Database\Schema;

/*
 * This file is part of the TYPO3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use TYPO3\CMS\Core\Type\Enumeration;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;

/**
 * List of database schema update types
 */
class SchemaUpdateType extends Enumeration
{
    /**
     * @var int
     */
    protected $value;

    /**
     * Add a field
     */
    const FIELD_ADD = 'field.add';

    /**
     * Change a field
     */
    const FIELD_CHANGE = 'field.change';

    /**
     * Drop a field
     */
    const FIELD_DROP = 'field.drop';

    /**
     * Add a table
     */
    const TABLE_ADD = 'table.add';

    /**
     * Change a table
     */
    const TABLE_CHANGE = 'table.change';

    /**
     * Drop a table
     */
    const TABLE_DROP = 'table.drop';

    /**
     * Truncate a table
     */
    const TABLE_CLEAR = 'table.clear';

    /**
     * Expands wildcards in schema update types, e.g. field.* or *.change
     *
     * @param array $schemaUpdateTypes List of schema update types
     * @return SchemaUpdateType[]
     * @throws InvalidEnumerationValueException If an invalid schema update type was passed
     */
    public static function expandSchemaUpdateTypes(array $schemaUpdateTypes)
    {
        $expandedSchemaUpdateTypes = array();
        $schemaUpdateTypeConstants = array_values(SchemaUpdateType::getConstants());

        // Collect total list of types by expanding wildcards
        foreach ($schemaUpdateTypes as $schemaUpdateType) {
            if (strpos($schemaUpdateType, '*') !== false) {
                $matchPattern = '/' . str_replace('\\*', '.+', preg_quote($schemaUpdateType, '/')) . '/';
                $matchingSchemaUpdateTypes = preg_grep($matchPattern, $schemaUpdateTypeConstants);
                $expandedSchemaUpdateTypes = array_merge($expandedSchemaUpdateTypes, $matchingSchemaUpdateTypes);
            } else {
                $expandedSchemaUpdateTypes[] = $schemaUpdateType;
            }
        }

        // Cast to enumeration objects to ensure valid values
        foreach ($expandedSchemaUpdateTypes as &$schemaUpdateType) {
            try {
                $schemaUpdateType = SchemaUpdateType::cast($schemaUpdateType);
            } catch (InvalidEnumerationValueException $e) {
                throw new InvalidEnumerationValueException(sprintf(
                    'Invalid schema update type "%s", must be one of: "%s"',
                    $schemaUpdateType,
                    implode('", "', $schemaUpdateTypeConstants)
                ), 1439460396);
            }
        }
        return $expandedSchemaUpdateTypes;
    }
}
