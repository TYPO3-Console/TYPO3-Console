<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Install\Upgrade;

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

class UpgradeWizardResult
{
    /**
     * @var array
     */
    private $sqlQueries;

    /**
     * @var array
     */
    private $messages;

    /**
     * @var bool
     */
    private $hasPerformed;

    public function __construct($hasPerformed, array $sqlQueries = [], array $messages = [])
    {
        $this->sqlQueries = $sqlQueries;
        $this->messages = $messages;
        $this->hasPerformed = $hasPerformed;
    }

    /**
     * @return array
     */
    public function getSqlQueries()
    {
        return $this->sqlQueries;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @return bool
     */
    public function hasPerformed()
    {
        return $this->hasPerformed;
    }
}
