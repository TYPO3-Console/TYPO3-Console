<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Install;

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

trait ExecuteActionWithArgumentsTrait
{
    /**
     * Executes the given action and outputs the serialized result messages
     *
     * @param string $actionName Name of the install step
     * @param array $arguments Arguments for the install step
     * @param bool $dryRun If true, do not execute the action, but only check if execution is necessary
     */
    private function executeActionWithArguments($actionName, array $arguments = [], $dryRun = false)
    {
        $this->outputLine(serialize($this->installStepActionExecutor->executeActionWithArguments($actionName, $arguments, $dryRun)));
    }
}
