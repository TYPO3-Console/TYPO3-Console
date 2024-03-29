<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command;

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

use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use TYPO3\CMS\Core\Console\CommandApplication as CoreCommandApplication;

class CommandApplication extends CoreCommandApplication
{
    public static function overrideApplication(CoreCommandApplication $commandApplication, Application $consoleApplication): void
    {
        $commandApplication->application = $consoleApplication;
    }
}
