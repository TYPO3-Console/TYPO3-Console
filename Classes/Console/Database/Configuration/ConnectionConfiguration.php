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
     * @param string|null $name
     * @return array
     */
    public function build(string $name = null): array
    {
        if ($name === null) {
            return $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
        }

        return $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$name];
    }

    public function getAvailableConnectionNames(string $type): array
    {
        return array_keys(
            array_filter(
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'],
                function (array $connectionConfig) use ($type) {
                    return strpos($connectionConfig['driver'], $type) !== false;
                }
            )
        );
    }
}
