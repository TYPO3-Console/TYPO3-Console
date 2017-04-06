<?php
namespace Helhum\Typo3Console\Mvc\Cli;

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

/**
 * Class InteractiveProcess
 */
class InteractiveProcess
{
    public function run($command)
    {
        $returnValue = 1;
        $pipes = [ ];
        $descriptors = [
            ['file', 'php://stdin', 'r'],   // stdin is a file that the child will read from
            ['file', 'php://stdout', 'w'],  // stdout is a file that the child will write to
            ['pipe', 'w'],                  // stderr is a file that the child will write to
        ];

        $process = @proc_open($command, $descriptors, $pipes);

        if (is_resource($process)) {
            $returnValue = proc_close($process);
        }
        return $returnValue;
    }
}
