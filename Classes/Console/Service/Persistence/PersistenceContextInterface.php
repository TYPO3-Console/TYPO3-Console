<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Service\Persistence;

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

interface PersistenceContextInterface
{
    /**
     * @param string $tableName
     * @throws TableDoesNotExistException
     * @return \Traversable
     */
    public function getAllRecordsOfTable($tableName);

    /**
     * @return int
     */
    public function countAllRecordsOfAllTables();

    /**
     * @param string $tableName
     * @return int
     */
    public function countLostIndexesOfRecordsInTable($tableName);

    /**
     * @param string $tableName
     * @return bool
     */
    public function deleteLostIndexesOfRecordsInTable($tableName);

    /**
     * @param array $processedTables
     * @return int
     */
    public function countLostTables(array $processedTables);

    /**
     * @param array $processedTables
     */
    public function deleteLostTables(array $processedTables);

    /**
     * @return array
     */
    public function getPersistenceConfiguration();
}
