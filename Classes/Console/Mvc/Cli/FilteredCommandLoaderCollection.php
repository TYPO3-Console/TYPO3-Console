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
class FilteredCommandLoaderCollection implements CommandLoaderInterface
{
    /**
     * @var CommandLoaderInterface
     */
    private $commandLoader;

    /**
     * @var array
     */
    private $ignoredCommandNames;

    /**
     * @var array
     */
    private $filteredCommandNames;

    public function __construct(CommandLoaderInterface $commandLoader, array $ignoredCommandNames)
    {
        $this->commandLoader = $commandLoader;
        $this->ignoredCommandNames = $ignoredCommandNames;
    }

    public function get($name)
    {
        if (!$this->has($name)) {
            throw new CommandNotFoundException(sprintf('The command "%s" does not exist.', $name), [], 1584103064);
        }

        return $this->commandLoader->get($name);
    }

    public function has($name): bool
    {
        return in_array($name, $this->getNames(), true);
    }

    public function getNames()
    {
        if ($this->filteredCommandNames) {
            return $this->filteredCommandNames;
        }
        $names = $this->commandLoader->getNames();

        return $this->filteredCommandNames = array_filter($names, function ($name) {
            return !in_array($name, $this->ignoredCommandNames, true);
        });
    }
}
