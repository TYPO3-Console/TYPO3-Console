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

/**
 * Represents a database schema update result
 */
class SchemaUpdateResult
{
    /**
     * @var array
     */
    protected $performedUpdates = [];

    /**
     * @var array
     */
    protected $errors = [];

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
     * Returns the list of performed update types including the count
     *
     * @return array
     */
    public function getPerformedUpdateTypes()
    {
        $typesCount = [];
        foreach ($this->performedUpdates as $type => $performedUpdates) {
            $typesCount[$type] = count($performedUpdates);
        }

        return $typesCount;
    }

    /**
     * Returns true if updates were performed, false otherwise
     *
     * @return bool
     */
    public function hasPerformedUpdates()
    {
        return count($this->performedUpdates) > 0;
    }

    /**
     * Adds to the number of updates performed for a schema update type
     *
     * @param SchemaUpdateType $schemaUpdateType Schema update type
     * @param array $updates Updates performed
     */
    public function addPerformedUpdates(SchemaUpdateType $schemaUpdateType, array $updates)
    {
        $this->performedUpdates[(string)$schemaUpdateType] = array_merge($this->performedUpdates[(string)$schemaUpdateType] ?? [], $updates);
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
     * @param array $statements SQL Statements that were executed
     */
    public function addErrors(SchemaUpdateType $schemaUpdateType, array $errors, array $statements = [])
    {
        $collectedErrors = [];
        foreach ($errors as $id => $error) {
            $collectedErrors[] = [
                'message' => $error,
                'statement' => $statements[$id],
            ];
        }
        $this->errors[(string)$schemaUpdateType] = array_merge($this->errors[(string)$schemaUpdateType] ?? [], $collectedErrors);
    }

    /**
     * Returns true if errors did occur during schema update, false otherwise
     *
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }
}
