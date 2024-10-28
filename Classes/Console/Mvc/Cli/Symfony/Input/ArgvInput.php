<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Mvc\Cli\Symfony\Input;

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

use Symfony\Component\Console\Input\InputInterface;

class ArgvInput extends \Symfony\Component\Console\Input\ArgvInput
{
    public function __construct(?InputInterface $input = null)
    {
        if ($input instanceof \Symfony\Component\Console\Input\ArgvInput) {
            $this->options = $input->options;
            $this->arguments = $input->arguments;
        }
        parent::__construct();
    }

    public function hasGivenOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    public function hasGivenArgument(string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }
}
