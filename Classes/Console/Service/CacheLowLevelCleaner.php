<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Service;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Flushes caches low level
 */
class CacheLowLevelCleaner
{
    /**
     * Recursively delete cache directory
     */
    public function forceFlushCachesFiles(): void
    {
        $cacheDirPattern = Environment::getVarPath() . '/cache/*/*';
        foreach (glob($cacheDirPattern) as $path) {
            if (!is_dir($path)) {
                continue;
            }
            GeneralUtility::rmdir($path, true);
            GeneralUtility::mkdir($path);
        }
    }
}
