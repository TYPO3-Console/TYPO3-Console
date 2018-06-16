<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Install\Action;

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

use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;

interface InstallActionInterface
{
    public function setOutput(ConsoleOutput $output);

    public function setCommandDispatcher(CommandDispatcher $commandDispatcher);

    public function shouldExecute(array $actionDefinition, array $options = []): bool;

    public function execute(array $actionDefinition, array $options = []): bool;
}
