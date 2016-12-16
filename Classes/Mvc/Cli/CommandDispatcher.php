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

use Symfony\Component\Process\ProcessBuilder;

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
    public function executeCommand($commandIdentifier, $arguments = [])
    {
        $commandLine = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];

        $processBuilder = new ProcessBuilder();
        $processBuilder->setPrefix(PHP_BINARY);
        $processBuilder->add(array_shift($commandLine));
        $processBuilder->add($commandIdentifier);

        foreach ($arguments as $argumentName => $argumentValue) {
            $dashedName = ucfirst($argumentName);
            $dashedName = preg_replace('/([A-Z][a-z0-9]+)/', '$1-', $dashedName);
            $dashedName = '--' . strtolower(substr($dashedName, 0, -1));
            $processBuilder->add($dashedName);
            $processBuilder->add($argumentValue);
        }

        $process = $processBuilder->getProcess();
        $exitCode = $process->run();
        $output = trim($process->getOutput());
        $errorOutput = trim($process->getErrorOutput());

        if ($exitCode > 0) {
            throw new \ErrorException(sprintf(
                'Executing %s failed with message:' . LF . LF . '"%s"' . LF . LF . 'and error:' . LF . LF . '"%s"' . LF,
                $commandIdentifier,
                $output,
                $errorOutput
            ));
        }

        return $output;
    }
}
