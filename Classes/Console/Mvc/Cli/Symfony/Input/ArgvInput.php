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

class ArgvInput extends \Symfony\Component\Console\Input\ArgvInput
{
    /**
     * @return bool
     */
    public function hasGivenOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * @return bool
     */
    public function hasGivenArgument(string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }
}
