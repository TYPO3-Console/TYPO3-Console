<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command;

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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated Will be removed with 6.0
 */
abstract class AbstractConvertedCommand extends Command
{
    private $synopsis = [];

    public function getNativeDefinition()
    {
        $definition = new InputDefinition($this->createNativeDefinition());
        $definition->addOptions($this->getApplication()->getDefinition()->getOptions());

        return $definition;
    }

    public function getSynopsis($short = false)
    {
        $key = $short ? 'short' : 'long';

        if (!isset($this->synopsis[$key])) {
            $this->synopsis[$key] = trim(sprintf('%s %s', $this->getName(), (new InputDefinition($this->createNativeDefinition()))->getSynopsis($short)));
        }

        return $this->synopsis[$key];
    }

    protected function createCompleteInputDefinition()
    {
        return array_merge($this->createNativeDefinition(), $this->createDeprecatedDefinition());
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->handleDeprecatedArgumentsAndOptions($input, $output);
    }

    abstract protected function createNativeDefinition(): array;

    abstract protected function createDeprecatedDefinition(): array;

    abstract protected function handleDeprecatedArgumentsAndOptions(InputInterface $input, OutputInterface $output);
}
