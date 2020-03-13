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

use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

/**
 * Represents a collection of commands
 *
 * This implementation pulls in the commands from various places,
 * mainly reading configuration files from TYPO3 extensions and composer packages
 */
class CommandLoaderCollection implements CommandLoaderInterface
{
    /**
     * @var CommandLoaderInterface[]
     */
    private $commandLoaderInterfaces;

    public function __construct(CommandLoaderInterface ...$commandLoaderInterfaces)
    {
        $this->commandLoaderInterfaces = $commandLoaderInterfaces;
    }

    public function get($name)
    {
        foreach ($this->commandLoaderInterfaces as $commandLoaderInterface) {
            if ($commandLoaderInterface->has($name)) {
                return $commandLoaderInterface->get($name);
            }
        }
        throw new CommandNotFoundException(sprintf('The command "%s" does not exist.', $name), [], 1583938998);
    }

    public function has($name)
    {
        foreach ($this->commandLoaderInterfaces as $commandLoaderInterface) {
            if ($commandLoaderInterface->has($name)) {
                return true;
            }
        }

        return false;
    }

    public function getNames()
    {
        $names = [];
        foreach ($this->commandLoaderInterfaces as $commandLoaderInterface) {
            $names[] = $commandLoaderInterface->getNames();
        }

        return array_merge(...$names);
    }
}
