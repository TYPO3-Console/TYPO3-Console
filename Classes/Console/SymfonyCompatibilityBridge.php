<?php
declare(strict_types=1);
namespace Helhum\Typo3Console;

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

use Symfony\Component\Console\Helper\Helper;

/**
 * Compatibility code to support symfony 5.0 and 6.0
 */
class SymfonyCompatibilityBridge
{
    /**
     * Method Helper::length() was added in Symfony 5.2,
     * while Helper::strlen() is needed for Symfony 5.0-5.1.
     *
     * See https://github.com/symfony/console/commit/f0671efd0e144681fd74ac1208ca0b5da8591b8a
     *
     * @param string $string
     * @return int
     */
    public static function helperLength(string $string)
    {
        if (method_exists(Helper::class, 'length')) {
            return Helper::length($string);
        }

        return Helper::strlen($string);
    }
}
