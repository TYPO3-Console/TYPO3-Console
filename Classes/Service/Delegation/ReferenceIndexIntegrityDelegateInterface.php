<?php
/*
 * This file is part of the typo3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

namespace Helhum\Typo3Console\Service\Delegation;

interface ReferenceIndexIntegrityDelegateInterface
{
    /**
     * @param int $unitsOfWorkCount
     * @return void
     */
    public function willStartOperation($unitsOfWorkCount);

    /**
     * @param string $tableName
     * @param array $record
     * @return void
     */
    public function willUpdateRecord($tableName, array $record);

    /**
     * @return void
     */
    public function operationHasEnded();

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger();
}
