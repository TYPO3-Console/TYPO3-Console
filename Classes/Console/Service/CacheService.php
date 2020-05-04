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

use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Cache Service handles all cache clearing related tasks
 */
class CacheService implements SingletonInterface
{
    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var array
     */
    protected $cacheConfiguration;

    public function __construct(array $cacheConfiguration = null)
    {
        // We need a new instance here to get the real caches instead of the disabled ones
        $this->cacheManager = new CacheManager();
        $this->cacheConfiguration = $cacheConfiguration ?? $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];
        $this->cacheManager->setCacheConfigurations($this->cacheConfiguration);
    }

    /**
     * Flushes all caches
     */
    public function flush()
    {
        $this->cacheManager->flushCaches();
    }

    /**
     * Flushes caches using the data handler.
     * Although we trigger the cache flush API here, the real intention is to trigger
     * hook subscribers, so that they can do their job (flushing "other" caches when cache is flushed).
     *
     * We use "all" because this method is only called from "flush" command which is indeed meant
     * to flush all caches. Besides that, "all" is really all caches starting from TYPO3 8.x
     * thus it would make sense for the hook subscribers to act on that cache clear type.
     *
     * However if you find a valid use case for us to also call "pages" here, then please create
     * a pull request and describe this case.
     */
    public function flushCachesWithDataHandler(): void
    {
        Bootstrap::initializeBackendUser(CommandLineUserAuthentication::class);
        Bootstrap::initializeBackendAuthentication();
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], []);
        $dataHandler->clear_cacheCmd('all');
    }

    /**
     * Flushes all caches in specified groups.
     *
     * @param array $groups
     * @throws NoSuchCacheGroupException
     * @return void
     */
    public function flushGroups(array $groups): void
    {
        $this->ensureCacheGroupsExist($groups);
        foreach ($groups as $group) {
            $this->cacheManager->flushCachesInGroup($group);
        }
    }

    /**
     * Flushes caches by given tags, optionally only in a specified (single) group.
     *
     * @param array $tags
     * @param string $group
     * @throws NoSuchCacheGroupException
     * @return void
     */
    public function flushByTags(array $tags, $group = null): void
    {
        foreach ($tags as $tag) {
            if ($group === null) {
                $this->cacheManager->flushCachesByTag($tag);
            } else {
                $this->cacheManager->flushCachesInGroupByTag($group, $tag);
            }
        }
    }

    /**
     * Flushes caches by tags, optionally only in specified groups.
     *
     * @param array $tags
     * @param array $groups
     * @throws NoSuchCacheGroupException
     * @return void
     */
    public function flushByTagsAndGroups(array $tags, array $groups = null): void
    {
        if ($groups === null) {
            $this->flushByTags($tags);
        } else {
            $this->ensureCacheGroupsExist($groups);
            foreach ($groups as $group) {
                $this->flushByTags($tags, $group);
            }
        }
    }

    public function getValidCacheGroups(): array
    {
        $validGroups = [];
        foreach ($this->cacheConfiguration as $cacheConfiguration) {
            if (isset($cacheConfiguration['groups']) && is_array($cacheConfiguration['groups'])) {
                $validGroups[] = $cacheConfiguration['groups'];
            }
        }

        return array_unique(array_merge(...$validGroups));
    }

    /**
     * @param array $groups
     * @throws NoSuchCacheGroupException
     */
    private function ensureCacheGroupsExist($groups): void
    {
        $validGroups = $this->getValidCacheGroups();
        $sanitizedGroups = array_intersect($groups, $validGroups);
        if (count($sanitizedGroups) !== count($groups)) {
            $invalidGroups = array_diff($groups, $sanitizedGroups);
            throw new NoSuchCacheGroupException('Invalid cache groups "' . implode(', ', $invalidGroups) . '".', 1399630162);
        }
    }
}
