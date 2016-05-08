<?php
namespace Helhum\Typo3Console\Mvc\Cli;

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

/**
 * Class CommandDispatcher
 */
class CommandDispatcher
{
    /**
     * @param string $commandIdentifier
     * @param array $arguments
     * @return string Json encoded output of the executed command
     * @throws \Exception
     */
    public function executeCommand($commandIdentifier, $arguments = array())
    {
        $commandLine = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();
        $callingScript = array_shift($commandLine);
        $commandLineArguments = array();
        $commandLineArguments[] = $commandIdentifier;

        foreach ($arguments as $argumentName => $argumentValue) {
            $dashedName = ucfirst($argumentName);
            $dashedName = preg_replace('/([A-Z][a-z0-9]+)/', '$1-', $dashedName);
            $dashedName = '--' . strtolower(substr($dashedName, 0, -1));
            $commandLineArguments[] = $dashedName;
            $commandLineArguments[] = $argumentValue;
        }

        $scriptToExecute = escapeshellcmd(PHP_BINARY) . ' ' . escapeshellcmd($callingScript) . ' ' . implode(' ', array_map('escapeshellarg', $commandLineArguments));
        $returnString = exec($scriptToExecute, $output, $returnValue);
        if ($returnValue > 0) {
            throw new \ErrorException(sprintf('Executing %s failed with message: ', $commandIdentifier) . LF . implode(LF, $output));
        }
        return $returnString;
    }
}
