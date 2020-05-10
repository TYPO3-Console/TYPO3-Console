<?php
declare(strict_types=1);
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
 * Represents command configuration provided by composer packages
 */
class CommandConfiguration
{
    /**
     * @var array
     */
    private $commandDefinitions;

    /**
     * @var array
     */
    private $replaces;

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * @return array
     */
    public function getCommandDefinitions(): array
    {
        return $this->commandDefinitions;
    }

    /**
     * @return array
     */
    public function getReplaces(): array
    {
        if ($this->replaces) {
            return $this->replaces;
        }
        $this->replaces = $replaces = [];
        foreach ($this->commandDefinitions as $commandConfiguration) {
            if (isset($commandConfiguration['replace'])) {
                $replaces[] = $commandConfiguration['replace'];
            }
        }

        return $this->replaces = array_merge($this->replaces, ...$replaces);
    }

    private function initialize(): void
    {
        $this->commandDefinitions = require __DIR__ . '/../../../../Configuration/ComposerPackagesCommands.php';
    }
}
