<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Mvc\Cli\Symfony\Command;

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

use Symfony\Component\Console\Command\Command;

/**
 * Only used to satisfy TYPO3, when TYPO3 Console is an extension
 * and the TYPO3 cli binary is used.
 */
class DummyCommand extends Command
{
    public function isEnabled()
    {
        return false;
    }
}
