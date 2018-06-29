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

use TYPO3\CMS\Core\Database\Connection;

class TableMatcher
{
    public function match(Connection $connection, string ...$expressions): array
    {
        $matchedTables = [];
        foreach ($expressions as $expression) {
            $matchesForExpression = $this->matchSingle($connection, $expression);
            if (!empty($matchesForExpression)) {
                array_push($matchedTables, ...$matchesForExpression);
            }
        }

        return $matchedTables;
    }

    private function matchSingle(Connection $connection, string $expression): array
    {
        return array_filter(
            $connection->getSchemaManager()->listTableNames(),
            function ($tableName) use ($expression) {
                return fnmatch($expression, $tableName);
            }
        );
    }
}
