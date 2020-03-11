<?php
declare(strict_types=1);
namespace Typo3Console\CreateReferenceCommand\ViewHelpers\Format;

/*
 * This file is part of the TYPO3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

/*                                                                        *
 * This script belongs to the Flow package "TYPO3.DocTools".              *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Returns the string as reStructuredText headline
 * using $lineCharacter and optionally $withOverline
 */
class HeadlineViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        $this->registerArgument('lineCharacter', 'string', 'The headline string', false, '-');
        $this->registerArgument('withOverline', 'bool', 'Add overline', false, false);
    }

    /**
     * @return string The formatted value
     */
    public function render()
    {
        $lineCharacter = $this->arguments['lineCharacter'];
        $withOverline = $this->arguments['withOverline'];
        $string = $this->renderChildren();
        $headLine = str_repeat($lineCharacter, strlen($string));

        if ($withOverline) {
            $string = $headLine . "\n" . $string;
        }

        return $string . "\n" . $headLine;
    }
}
