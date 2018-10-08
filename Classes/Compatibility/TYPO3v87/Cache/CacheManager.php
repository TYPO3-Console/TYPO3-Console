<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\TYPO3v87\Cache;

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

class CacheManager extends \TYPO3\CMS\Core\Cache\CacheManager
{
    /**
     * @var bool
     */
    private $disableCaching;

    public function __construct(bool $disableCaching = false)
    {
        $this->disableCaching = $disableCaching;
    }

    public function setCacheConfigurations(array $cacheConfigurations)
    {
        if ($this->disableCaching) {
            foreach ($cacheConfigurations as &$cacheConfiguration) {
                $cacheConfiguration['backend'] = \TYPO3\CMS\Core\Cache\Backend\NullBackend::class;
                $cacheConfiguration['options'] = [];
            }
            unset($cacheConfiguration);
        }
        parent::setCacheConfigurations($cacheConfigurations);
    }
}
