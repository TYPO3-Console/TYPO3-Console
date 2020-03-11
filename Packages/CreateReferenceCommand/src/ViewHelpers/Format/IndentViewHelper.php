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
 * Renders it's children and replaces every newline by a combination of
 * newline and $indent.
 */
class IndentViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        $this->registerArgument('indent', 'string', 'String used to indent', false, "\t");
        $this->registerArgument('inline', 'boolean', 'If TRUE, the first line will not be indented', false, false);
    }

    /**
     * @return string The formatted value
     */
    public function render()
    {
        $indent = $this->arguments['indent'];
        $inline = $this->arguments['inline'];
        $string = $this->renderChildren();

        return ($inline === false ? $indent : '') . str_replace("\n", "\n" . $indent, $string);
    }
}
