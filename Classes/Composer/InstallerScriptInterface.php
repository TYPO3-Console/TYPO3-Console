<?php
namespace Helhum\Typo3Console\Composer;

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

use Composer\Script\Event as ScriptEvent;

interface InstallerScriptInterface
{
    /**
     * This method is called first. setupConsole is not called if this returns false
     *
     * @param ScriptEvent $event
     * @return bool
     */
    public function shouldRun(ScriptEvent $event);

    /**
     * This is executed, when shouldRun returned true
     *
     * @param ScriptEvent $event
     * @throws \RuntimeException
     * @return bool Return false if the script failed
     */
    public function run(ScriptEvent $event);
}
