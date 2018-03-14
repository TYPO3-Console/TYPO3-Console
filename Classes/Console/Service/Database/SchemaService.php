<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Service\Database;

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

use Helhum\Typo3Console\Database\Schema\SchemaUpdateInterface;
use Helhum\Typo3Console\Database\Schema\SchemaUpdateResult;
use Helhum\Typo3Console\Database\Schema\SchemaUpdateType;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Service for database schema migrations
 */
class SchemaService implements SingletonInterface
{
    /**
     * @var SchemaUpdateInterface
     */
    private $schemaUpdate;

    /**
     * @var Dispatcher
     */
    private $signalSlotDispatcher;

    /**
     * SchemaService constructor.
     *
     * @param SchemaUpdateInterface $schemaUpdate
     * @param Dispatcher $signalSlotDispatcher
     */
    public function __construct(SchemaUpdateInterface $schemaUpdate, Dispatcher $signalSlotDispatcher)
    {
        $this->schemaUpdate = $schemaUpdate;
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * Perform necessary database schema migrations
     *
     * @param SchemaUpdateType[] $schemaUpdateTypes List of permitted schema update types
     * @param bool $dryRun If true, the database operations are not performed
     * @return SchemaUpdateResult Result of the schema update
     */
    public function updateSchema(array $schemaUpdateTypes, $dryRun = false)
    {
        $updateStatements = [
            SchemaUpdateType::GROUP_SAFE => $this->schemaUpdate->getSafeUpdates(),
            SchemaUpdateType::GROUP_DESTRUCTIVE => $this->schemaUpdate->getDestructiveUpdates(),
        ];

        $updateResult = new SchemaUpdateResult();

        foreach ($schemaUpdateTypes as $schemaUpdateType) {
            foreach ($schemaUpdateType->getStatementTypes() as $statementType => $statementGroup) {
                if (isset($updateStatements[$statementGroup][$statementType])) {
                    $statements = $updateStatements[$statementGroup][$statementType];
                    if (empty($statements)) {
                        continue;
                    }
                    if ($dryRun) {
                        $updateResult->addPerformedUpdates($schemaUpdateType, $statements);
                    } else {
                        $result = $this->schemaUpdate->migrate(
                            $statements,
                            // Generate a map of statements as keys and true as values
                            array_combine(array_keys($statements), array_fill(0, count($statements), true))
                        );
                        if (empty($result)) {
                            $updateResult->addPerformedUpdates($schemaUpdateType, $statements);
                        } else {
                            $updateResult->addErrors($schemaUpdateType, $result, $statements);
                        }
                    }
                }
            }
        }

        $this->emitDatabaseUpdateSignal($updateResult);

        return $updateResult;
    }

    /**
     * Emits database update executed signal
     * @param SchemaUpdateResult $updateResult
     */
    private function emitDatabaseUpdateSignal($updateResult)
    {
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'afterDatabaseUpdate', [$updateResult]);
    }
}
