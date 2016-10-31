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
     * Add a field
     */
    const FIELD_ADD = 'field.add';

    /**
     * Change a field
     */
    const FIELD_CHANGE = 'field.change';

    /**
    * Prefix a field
    */
    const FIELD_PREFIX = 'field.prefix';

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
    * Prefix a table
    */
    const TABLE_PREFIX = 'table.prefix';

    /**
     * Drop a table
     */
    const TABLE_DROP = 'table.drop';

    /**
     * Truncate a table
     */
    const TABLE_CLEAR = 'table.clear';

    /**
     * All safe update types
     */
    const GROUP_SAFE = 'all.safe';

    /**
     * All destructive update types
     */
    const GROUP_DESTRUCTIVE = 'all.destructive';

    /**
     * Mapping of schema update types to internal statement types
     *
     * @var array
     */
    private static $schemaUpdateTypesStatementTypesMapping = [
        self::FIELD_ADD => ['add' => self::GROUP_SAFE],
        self::FIELD_CHANGE => ['change' => self::GROUP_SAFE],
        self::FIELD_PREFIX => ['change' => self::GROUP_DESTRUCTIVE],
        self::FIELD_DROP => ['drop' => self::GROUP_DESTRUCTIVE],
        self::TABLE_ADD => ['create_table' => self::GROUP_SAFE],
        self::TABLE_CHANGE => ['change_table' => self::GROUP_SAFE],
        self::TABLE_CLEAR => ['clear_table' => self::GROUP_DESTRUCTIVE],
        self::TABLE_PREFIX => ['change_table' => self::GROUP_DESTRUCTIVE],
        self::TABLE_DROP => ['drop_table' => self::GROUP_DESTRUCTIVE],
        self::GROUP_SAFE => [
            'add' => self::GROUP_SAFE,
            'change' => self::GROUP_SAFE,
            'create_table' => self::GROUP_SAFE,
            'change_table' => self::GROUP_SAFE,
        ],
        self::GROUP_DESTRUCTIVE => [
            'change' => self::GROUP_DESTRUCTIVE,
            'drop' => self::GROUP_DESTRUCTIVE,
            'clear_table' => self::GROUP_DESTRUCTIVE,
            'change_table' => self::GROUP_DESTRUCTIVE,
            'drop_table' => self::GROUP_DESTRUCTIVE,
        ],
    ];

    /**
     * Expands wildcards in schema update types, e.g. field.* or *.change
     *
     * @param array $schemaUpdateTypes List of schema update types
     * @return SchemaUpdateType[]
     * @throws InvalidEnumerationValueException If an invalid schema update type was passed
     */
    public static function expandSchemaUpdateTypes(array $schemaUpdateTypes)
    {
        $expandedSchemaUpdateTypes = [];
        $schemaUpdateTypeConstants = array_values(self::getConstants());
        $newUpdateTypes = [];

        // Expand groups first
        foreach ($schemaUpdateTypes as $schemaUpdateType) {
            if ($schemaUpdateType === 'all.safe') {
                $newUpdateTypes[] = '*.add';
                $newUpdateTypes[] = '*.change';
            } elseif ($schemaUpdateType === 'all.destructive') {
                $newUpdateTypes[] = '*.prefix';
                $newUpdateTypes[] = '*.drop';
                $newUpdateTypes[] = 'table.clear';
            } elseif ($schemaUpdateType === 'all.*') {
                $newUpdateTypes[] = '__invalid';
            } else {
                $newUpdateTypes[] = $schemaUpdateType;
            }
        }

        // Collect total list of types by expanding wildcards
        foreach ($newUpdateTypes as $schemaUpdateType) {
            if (strpos($schemaUpdateType, '*') !== false) {
                if ($schemaUpdateType === '*') {
                    $matchPattern = '/^(?!all.).+/';
                } else {
                    $matchPattern = '/' . str_replace('\\*', '.+', preg_quote($schemaUpdateType, '/')) . '/';
                }
                $matchingSchemaUpdateTypes = preg_grep($matchPattern, $schemaUpdateTypeConstants);
                if (!empty($matchingSchemaUpdateTypes)) {
                    $expandedSchemaUpdateTypes = array_merge($expandedSchemaUpdateTypes, $matchingSchemaUpdateTypes);
                } else {
                    // No matches, add it here to let it fail later
                    $expandedSchemaUpdateTypes[] = $schemaUpdateType;
                }
            } else {
                $expandedSchemaUpdateTypes[] = $schemaUpdateType;
            }
        }

        // Cast to enumeration objects to ensure valid values
        foreach ($expandedSchemaUpdateTypes as &$schemaUpdateType) {
            try {
                $schemaUpdateType = self::cast($schemaUpdateType);
            } catch (InvalidEnumerationValueException $e) {
                throw new InvalidEnumerationValueException(
                    sprintf(
                        'Invalid schema update type "%s", must be one of: "%s"',
                        $schemaUpdateType,
                        implode('", "', $schemaUpdateTypeConstants)
                    ),
                    1439460396,
                    $e
                );
            }
        }

        return $expandedSchemaUpdateTypes;
    }

    /**
     * Map schema update type to a list of internal statement types
     *
     * @return array
     */
    public function getStatementTypes()
    {
        return self::$schemaUpdateTypesStatementTypesMapping[(string)$this];
    }
}
