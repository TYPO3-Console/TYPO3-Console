<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Database\Schema;

/*
 * This file is part of the TYPO3 Console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationDefinitionException;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;

/**
 * List of database schema update types
 */
class SchemaUpdateType
{
    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var array
     */
    protected static $enumConstants;

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
     * All safe update types
     */
    const GROUP_SAFE = 'safe';

    /**
     * All destructive update types
     */
    const GROUP_DESTRUCTIVE = 'destructive';

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
        self::TABLE_PREFIX => ['change_table' => self::GROUP_DESTRUCTIVE],
        self::TABLE_DROP => ['drop_table' => self::GROUP_DESTRUCTIVE],
        self::GROUP_SAFE => [
            self::FIELD_ADD,
            self::FIELD_CHANGE,
            self::TABLE_ADD,
            self::TABLE_CHANGE,
        ],
        self::GROUP_DESTRUCTIVE => [
            self::FIELD_PREFIX,
            self::FIELD_DROP,
            self::TABLE_PREFIX,
            self::TABLE_DROP,
        ],
        '*' => [
            self::FIELD_ADD,
            self::FIELD_CHANGE,
            self::FIELD_PREFIX,
            self::FIELD_DROP,
            self::TABLE_ADD,
            self::TABLE_CHANGE,
            self::TABLE_PREFIX,
            self::TABLE_DROP,
        ],
    ];

    /**
     * @param mixed $value
     */
    public function __construct($value = null)
    {
        if ($value === null && !defined('static::__default')) {
            throw new InvalidEnumerationValueException(
                sprintf('A value for enumeration "%s" is required if no __default is defined.', static::class),
                1381512753
            );
        }
        static::loadValues();
        if (!$this->isValid($value)) {
            throw new InvalidEnumerationValueException(
                sprintf('Invalid value "%s" for enumeration "%s"', $value, static::class),
                1381512761
            );
        }
        $this->setValue($value);
    }

    /**
     * Expands wildcards in schema update types, e.g. field.* or *.change
     *
     * @param array $schemaUpdateTypes List of schema update types
     * @throws InvalidEnumerationValueException If an invalid schema update type was passed
     * @return SchemaUpdateType[]
     */
    public static function expandSchemaUpdateTypes(array $schemaUpdateTypes)
    {
        $expandedSchemaUpdateTypes = [];
        $schemaUpdateTypeConstants = array_values(self::getConstants());

        // Collect total list of types by expanding wildcards
        foreach ($schemaUpdateTypes as $schemaUpdateType) {
            if (in_array($schemaUpdateType, ['*', 'safe', 'destructive'], true)) {
                if (empty(self::$schemaUpdateTypesStatementTypesMapping[$schemaUpdateType])) {
                    throw new InvalidEnumerationValueException(
                        sprintf(
                            'Invalid schema update type "%s", must be one of: "%s"',
                            $schemaUpdateType,
                            implode('", "', $schemaUpdateTypeConstants)
                        ),
                        1477998197
                    );
                }
                foreach (self::$schemaUpdateTypesStatementTypesMapping[$schemaUpdateType] as $matchedType) {
                    $expandedSchemaUpdateTypes[] = $matchedType;
                }
            } elseif (strpos($schemaUpdateType, '*') !== false) {
                $matchPattern = '/' . str_replace('\\*', '.+', preg_quote($schemaUpdateType, '/')) . '/';
                $matchingSchemaUpdateTypes = preg_grep($matchPattern, $schemaUpdateTypeConstants);
                if (empty($matchingSchemaUpdateTypes)) {
                    throw new InvalidEnumerationValueException(
                        sprintf(
                            'Invalid schema update type "%s", must be one of: "%s"',
                            $schemaUpdateType,
                            implode('", "', $schemaUpdateTypeConstants)
                        ),
                        1477998245
                    );
                }
                foreach ($matchingSchemaUpdateTypes as $matchingSchemaUpdateType) {
                    $expandedSchemaUpdateTypes[] = $matchingSchemaUpdateType;
                }
            } else {
                $expandedSchemaUpdateTypes[] = $schemaUpdateType;
            }
        }
        $expandedSchemaUpdateTypes = array_unique($expandedSchemaUpdateTypes);
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

    /**
     * Get the valid values for this enum
     * Defaults to constants you define in your subclass
     * override to provide custom functionality
     *
     * @param bool $include_default
     * @return array
     */
    public static function getConstants($include_default = false)
    {
        static::loadValues();
        $enumConstants = static::$enumConstants[static::class];
        if (!$include_default) {
            unset($enumConstants['__default']);
        }

        return $enumConstants;
    }

    /**
     * Cast value to enumeration type
     *
     * @param mixed $value Value that has to be casted
     * @return static
     */
    public static function cast($value)
    {
        if (!is_object($value) || get_class($value) !== static::class) {
            $value = new static($value);
        }

        return $value;
    }

    /**
     * @internal param string $class
     */
    protected static function loadValues()
    {
        $class = static::class;

        if (isset(static::$enumConstants[$class])) {
            return;
        }

        $reflection = new \ReflectionClass($class);
        $constants = $reflection->getConstants();
        $defaultValue = null;
        if (isset($constants['__default'])) {
            $defaultValue = $constants['__default'];
            unset($constants['__default']);
        }
        if (empty($constants)) {
            throw new InvalidEnumerationValueException(
                sprintf(
                    'No constants defined in enumeration "%s"',
                    $class
                ),
                1381512807
            );
        }
        foreach ($constants as $constant => $value) {
            if (!is_int($value) && !is_string($value)) {
                throw new InvalidEnumerationDefinitionException(
                    sprintf(
                        'Constant value "%s" of enumeration "%s" must be of type integer or string, got "%s" instead',
                        $constant,
                        $class,
                        get_debug_type($value)
                    ),
                    1381512797
                );
            }
        }
        $constantValueCounts = array_count_values($constants);
        arsort($constantValueCounts, SORT_NUMERIC);
        $constantValueCount = current($constantValueCounts);
        $constant = key($constantValueCounts);
        if ($constantValueCount > 1) {
            throw new InvalidEnumerationDefinitionException(
                sprintf(
                    'Constant value "%s" of enumeration "%s" is not unique (defined %d times)',
                    $constant,
                    $class,
                    $constantValueCount
                ),
                1381512859
            );
        }
        if ($defaultValue !== null) {
            $constants['__default'] = $defaultValue;
        }
        static::$enumConstants[$class] = $constants;
    }

    /**
     * Set the Enumeration value to the associated enumeration value by a loose comparison.
     * The value, that is used as the enumeration value, will be of the same type like defined in the enumeration
     *
     * @param mixed $value
     * @throws Exception\InvalidEnumerationValueException
     */
    protected function setValue($value)
    {
        $enumKey = array_search((string)$value, static::$enumConstants[static::class]);
        if ($enumKey === false) {
            throw new InvalidEnumerationValueException(
                sprintf('Invalid value "%s" for enumeration "%s"', $value, __CLASS__),
                1381615295
            );
        }
        $this->value = static::$enumConstants[static::class][$enumKey];
    }

    /**
     * Check if the value on this enum is a valid value for the enum
     *
     * @param mixed $value
     * @return bool
     */
    protected function isValid($value)
    {
        $value = (string)$value;
        foreach (static::$enumConstants[static::class] as $constantValue) {
            if ($value === (string)$constantValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }
}
