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

/**
 * Represents a database schema update result
 */
class SchemaUpdateResult
{
    /**
     * @var array $performedUpdates
     */
    protected $performedUpdates = array();

    /**
     * @var array $errors
     */
    protected $errors = array();

    /**
     * Returns the list of performed updates, grouped by schema update type
     *
     * @return array
     */
    public function getPerformedUpdates()
    {
        return $this->performedUpdates;
    }

    /**
     * Returns true if updates where performed, false otherwise
     *
     * @return bool
     */
    public function hasPerformedUpdates()
    {
        return count($this->performedUpdates);
    }

    /**
     * Adds to the number of updates performed for a schema update type
     *
     * @param SchemaUpdateType $schemaUpdateType Schema update type
     * @param int $numberOfUpdates Number of updates performed
     */
    public function addPerformedUpdates(SchemaUpdateType $schemaUpdateType, $numberOfUpdates)
    {
        $this->performedUpdates[(string)$schemaUpdateType] += $numberOfUpdates;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Adds to the list of errors occurred for a schema update type
     *
     * @param SchemaUpdateType $schemaUpdateType Schema update type
     * @param array $errors List of error messages
     */
    public function addErrors(SchemaUpdateType $schemaUpdateType, array $errors)
    {
        $this->errors[(string)$schemaUpdateType] = array_merge((array)$this->errors[(string)$schemaUpdateType], $errors);
    }

    /**
     * Returns true if errors did occur during schema update, false otherwise
     *
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors);
    }
}
