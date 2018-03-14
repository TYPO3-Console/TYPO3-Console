<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Database\Configuration;

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

class ConnectionConfiguration
{
    /**
     * Returns a normalized DB configuration array
     *
     * @return array
     */
    public function build()
    {
        return $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
    }
}
