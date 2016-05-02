<?php
namespace Helhum\Typo3Console\Install\Status;

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

use TYPO3\CMS\Install\Status\AbstractStatus;
use TYPO3\CMS\Install\Status\StatusInterface;

/**
 * Redirect level status - Used in cli setup dispatcher
 */
class RedirectStatus extends AbstractStatus implements StatusInterface
{
    /**
     * @var string The severity
     */
    protected $severity = 'redirect';
}
