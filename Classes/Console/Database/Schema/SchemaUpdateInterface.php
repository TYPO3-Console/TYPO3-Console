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
 * The database schema update contract
 */
interface SchemaUpdateInterface
{
    /**
     * Get all schema updates that are considered (relatively) safe
     *
     * @return array
     */
    public function getSafeUpdates();

    /**
     * Get all schema updates that are destructive (renaming/ deleting fields/ tables)
     *
     * @return array
     */
    public function getDestructiveUpdates();

    /**
     * Actually execute the migration to the new schema
     *
     * @param array $statements
     * @param array $selectedStatements
     * @return array
     */
    public function migrate(array $statements, array $selectedStatements);
}
