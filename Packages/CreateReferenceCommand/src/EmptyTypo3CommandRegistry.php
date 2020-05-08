<?php
declare(strict_types=1);
namespace Typo3Console\CreateReferenceCommand;

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

/*                                                                        *
 * This script belongs to the Flow package "TYPO3.DocTools".              *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Helhum\Typo3Console\Mvc\Cli\Typo3CommandRegistry;

/**
 * "Command Reference" command controller for the Documentation package.
 * Used to create reference documentation for TYPO3 Console CLI commands.
 */
class EmptyTypo3CommandRegistry extends Typo3CommandRegistry
{
    public function __construct()
    {
    }

    protected function populateCommandsFromPackages()
    {
    }
}
