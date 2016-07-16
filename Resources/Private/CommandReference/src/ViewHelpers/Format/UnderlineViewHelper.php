<?php
namespace Helhum\Typo3Console\ViewHelpers\Format;

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

/**
 * Returns the string, a newline character and an underline made of
 * $withCharacter as long as the original string.
 */
class UnderlineViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * @param string $withCharacter The padding string
     * @return string The formatted value
     */
    public function render($withCharacter = '-')
    {
        $string = $this->renderChildren();
        return $string . chr(10) . str_repeat($withCharacter, strlen($string));
    }
}
