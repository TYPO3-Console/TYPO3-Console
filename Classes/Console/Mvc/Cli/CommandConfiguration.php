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

use Symfony\Component\Console\Exception\RuntimeException;

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
     * @param mixed $commandConfiguration
     * @param string $packageName
     * @throws RuntimeException
     */
    public static function ensureValidCommandRegistration($commandConfiguration, $packageName): void
    {
        if (
            !is_array($commandConfiguration)
            || !isset($commandConfiguration['commands'])
            || !is_array($commandConfiguration['commands'])
        ) {
            throw new RuntimeException($packageName . ' defines invalid commands in Configuration/Console/Commands.php', 1461186959);
        }
    }

    public static function unifyCommandConfiguration(array $commandConfiguration, string $packageName): array
    {
        $commandDefinitions = [];

        foreach ($commandConfiguration['commands'] ?? [] as $commandName => $commandConfig) {
            $vendor = $commandConfig['vendor'] ?? $packageName;
            $nameSpacedCommandName = $vendor . ':' . $commandName;
            $commandConfig['vendor'] = $vendor;
            $commandConfig['name'] = $commandName;
            $commandConfig['nameSpacedName'] = $nameSpacedCommandName;
            $commandDefinitions[] = $commandConfig;
        }

        return $commandDefinitions;
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
        $this->commandDefinitions = array_merge([], ...$this->getComposerPackagesCommands());
    }

    /**
     * @return array
     */
    private function getComposerPackagesCommands(): array
    {
        return require __DIR__ . '/../../../../Configuration/ComposerPackagesCommands.php';
    }
}
