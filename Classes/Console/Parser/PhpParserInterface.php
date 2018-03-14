<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Parser;

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
 * Interface PhpParserInterface
 */
interface PhpParserInterface
{
    /**
     * @param string $classFile Path to PHP class file
     * @throws ParsingException
     * @return ParsedClass
     */
    public function parseClassFile($classFile);

    /**
     * @param string $classContent
     * @return ParsedClass
     */
    public function parseClass($classContent);
}
