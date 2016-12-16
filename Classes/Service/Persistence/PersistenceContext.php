<?php
namespace Helhum\Typo3Console\Service\Persistence;

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

use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Class PersistenceContext
 */
class PersistenceContext
{
    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var array
     */
    protected $persistenceConfiguration = [];

    public function __construct(DatabaseConnection $databaseConnection, array $persistenceConfiguration)
    {
        $this->databaseConnection = $databaseConnection;
        $this->persistenceConfiguration = $persistenceConfiguration;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    public function getDatabaseConnection()
    {
        return $this->databaseConnection;
    }

    /**
     * @return array
     */
    public function getPersistenceConfiguration()
    {
        return $this->persistenceConfiguration;
    }
}
