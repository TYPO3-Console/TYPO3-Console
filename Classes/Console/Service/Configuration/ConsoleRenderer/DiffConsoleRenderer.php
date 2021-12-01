<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Service\Configuration\ConsoleRenderer;

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

use cogpowered\FineDiff\Render\Renderer;

/**
 * Renderer for the diff package, optimized for console output
 */
class DiffConsoleRenderer extends Renderer
{
    public function callback(string $opcode, string $from, int $from_offset, int $from_len): string
    {
        if ($opcode === 'c') {
            $output = substr($from, $from_offset, $from_len);
        } elseif ($opcode === 'd') {
            $deletion = substr($from, $from_offset, $from_len);
            $deletion = preg_replace('/^(.)/m', '-$1 ', $deletion);
            $output = '<del>' . $deletion . '</del>';
        } else {/* if ( $opcode === 'i' ) */
            $addition = substr($from, $from_offset, $from_len);
            $addition = preg_replace('/^(.)/m', '+$1 ', $addition);
            $output = '<ins>' . $addition . '</ins>';
        }

        return $output;
    }
}
