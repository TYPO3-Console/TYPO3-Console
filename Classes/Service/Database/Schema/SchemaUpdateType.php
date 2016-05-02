<?php
namespace Helhum\Typo3Console\Service\Database\Schema;

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
}
