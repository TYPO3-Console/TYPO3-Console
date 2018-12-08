<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Exception;

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

use Helhum\Typo3Console\Exception;
use Throwable;

class CommandNotAvailableException extends Exception
{
    /**
     * @var string
     */
    private $commandName;

    public function __construct(string $commandName, string $message = '', int $code = 0, Throwable $previous = null)
    {
        $this->commandName = $commandName;
        $message = $message ?: sprintf('Command "%s" is not available in this context', $commandName);
        $code = $code ?: 1544277403;
        parent::__construct($message, $code, $previous);
    }

    public function getCommandName(): string
    {
        return $this->commandName;
    }
}
