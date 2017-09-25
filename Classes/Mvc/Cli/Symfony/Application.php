<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Mvc\Cli\Symfony;

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

use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    const TYPO3_CONSOLE_VERSION = '5.0.0';

    public function __construct()
    {
        parent::__construct('TYPO3 Console', self::TYPO3_CONSOLE_VERSION);
    }
}
