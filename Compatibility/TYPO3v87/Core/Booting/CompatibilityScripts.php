<?php
namespace Helhum\Typo3Console\TYPO3v87\Core\Booting;

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

use TYPO3\CMS\Core\Core\Bootstrap;

class CompatibilityScripts
{
    /**
     * @param Bootstrap $bootstrap
     */
    public static function initializeConfigurationManagement(Bootstrap $bootstrap)
    {
        \Closure::bind(function () use ($bootstrap) {
            // Because links might be generated from CLI (e.g. by Solr indexer)
            // We need to properly initialize the cache hash calculator here!
            $bootstrap->setCacheHashOptions();
            $bootstrap->defineUserAgentConstant();
        }, null, $bootstrap)();
    }

    /**
     * @param Bootstrap $bootstrap
     */
    public static function initializeDatabaseConnection(Bootstrap $bootstrap)
    {
        $bootstrap->initializeTypo3DbGlobal();
    }

    /**
     * @param Bootstrap $bootstrap
     */
    public static function initializeExtensionConfiguration(Bootstrap $bootstrap)
    {
        $bootstrap->defineLoggingAndExceptionConstants();
    }
}
