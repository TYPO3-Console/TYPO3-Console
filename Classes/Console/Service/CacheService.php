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

use Helhum\Typo3Console\Core\Booting\CompatibilityScripts;
use Helhum\Typo3Console\Core\Booting\Scripts;
use Helhum\Typo3Console\Service\Configuration\ConfigurationService;
use Symfony\Component\Console\Exception\RuntimeException;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
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
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * @var Bootstrap
     */
    private $bootstrap;

    /**
     * @var CacheLowLevelCleaner
     */
    private $lowLevelCleaner;

    /**
     * Builds the dependencies correctly
     *
     * @param ConfigurationService $configurationService
     */
    public function __construct(ConfigurationService $configurationService, Bootstrap $bootstrap = null, CacheLowLevelCleaner $lowLevelCleaner = null)
    {
        // We need a new instance here to get the real caches instead of the disabled ones
        $this->cacheManager = new CacheManager();
        $this->cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
        $this->configurationService = $configurationService;
        $this->bootstrap = $bootstrap ?: Bootstrap::getInstance();
        $this->lowLevelCleaner = $lowLevelCleaner ?: new CacheLowLevelCleaner();
    }

    /**
     * Flushes all caches
     *
     * @param bool $force
     */
    public function flush($force = false)
    {
        $this->ensureDatabaseIsInitialized();
        if ($force) {
            $this->lowLevelCleaner->forceFlushCachesFiles();
            $this->lowLevelCleaner->forceFlushDatabaseCacheTables();
        }
        $this->cacheManager->flushCaches();
    }

    /**
     * Flushes all file based caches
     *
     * @param bool $force
     */
    public function flushFileCaches($force = false)
    {
        if ($force) {
            $this->lowLevelCleaner->forceFlushCachesFiles();
        }
        foreach ($this->getFileCaches() as $cache) {
            $cache->flush();
        }
    }

    /**
     * Flushes caches using the data handler. This should not be necessary any more in the future.
     * Although we trigger the cache flush API here, the real intention is to trigger
     * hook subscribers, so that they can do their job (flushing "other" caches when cache is flushed.
     * For example realurl subscribes to these hooks.
     *
     * We use "all" because this method is only called from "flush" command which is indeed meant
     * to flush all caches. Besides that, "all" is really all caches starting from TYPO3 8.x
     * thus it would make sense for the hook subscribers to act on that cache clear type.
     *
     * However if you find a valid use case for us to also call "pages" here, then please create
     * a pull request and describe this case. "system" or "temp_cached" will not be added however
     * because these are deprecated since TYPO3 8.x
     *
     * Besides that, this DataHandler API is probably something to be removed in TYPO3,
     * so we deprecate and mark this method as internal at the same time.
     *
     * @deprecated Will be removed once DataHandler cache flush methods are removed in supported TYPO3 versions
     * @internal
     */
    public function flushCachesWithDataHandler()
    {
        $this->ensureDatabaseIsInitialized();
        $this->ensureBackendUserIsInitialized();
        self::createDataHandlerFromGlobals()->clear_cacheCmd('all');
    }

    /**
     * Flushes all caches in specified groups.
     *
     * @param array $groups
     * @throws NoSuchCacheGroupException
     */
    public function flushGroups(array $groups)
    {
        $this->ensureCacheGroupsExist($groups);
        $this->ensureDatabaseIsInitialized();
        foreach ($groups as $group) {
            $this->cacheManager->flushCachesInGroup($group);
        }
    }

    /**
     * Flushes caches by given tags, optionally only in a specified (single) group.
     *
     * @param array $tags
     * @param string $group
     */
    public function flushByTags(array $tags, $group = null)
    {
        $this->ensureDatabaseIsInitialized();
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
     */
    public function flushByTagsAndGroups(array $tags, array $groups = null)
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

    /**
     * @return array
     */
    public function getValidCacheGroups()
    {
        $validGroups = [];
        foreach ($this->configurationService->getActive('SYS/caching/cacheConfigurations') as $cacheConfiguration) {
            if (isset($cacheConfiguration['groups']) && is_array($cacheConfiguration['groups'])) {
                $validGroups = array_merge($validGroups, $cacheConfiguration['groups']);
            }
        }

        return array_unique($validGroups);
    }

    /**
     * @deprecated can be removed when TYPO3 8 support is removed
     */
    private function ensureDatabaseIsInitialized()
    {
        if (!empty($GLOBALS['TYPO3_DB'])) {
            // Already initialized
            return;
        }
        CompatibilityScripts::initializeDatabaseConnection($this->bootstrap);
    }

    private function ensureBackendUserIsInitialized()
    {
        if (!empty($GLOBALS['BE_USER'])) {
            // Already initialized
            return;
        }
        Scripts::initializePersistence($this->bootstrap);
        Scripts::initializeAuthenticatedOperations($this->bootstrap);
    }

    /**
     * @param array $groups
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException
     */
    private function ensureCacheGroupsExist($groups)
    {
        $validGroups = $this->getValidCacheGroups();
        $sanitizedGroups = array_intersect($groups, $validGroups);
        if (count($sanitizedGroups) !== count($groups)) {
            $invalidGroups = array_diff($groups, $sanitizedGroups);
            throw new NoSuchCacheGroupException('Invalid cache groups "' . implode(', ', $invalidGroups) . '".', 1399630162);
        }
    }

    /**
     * @return FrontendInterface[]
     */
    private function getFileCaches()
    {
        $fileCaches = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] as $identifier => $cacheConfiguration) {
            if (
                isset($cacheConfiguration['backend'])
                && (
                    $cacheConfiguration['backend'] === SimpleFileBackend::class
                    || is_subclass_of($cacheConfiguration['backend'], SimpleFileBackend::class)
                )
            ) {
                $fileCaches[] = $this->cacheManager->getCache($identifier);
            }
        }

        return $fileCaches;
    }

    /**
     * Create a data handler instance from global state (with user being admin)
     *
     * @internal
     * @throws RuntimeException
     * @return DataHandler
     */
    private static function createDataHandlerFromGlobals()
    {
        if (empty($GLOBALS['BE_USER']) || !$GLOBALS['BE_USER'] instanceof BackendUserAuthentication) {
            throw new RuntimeException('No backend user initialized. flushCachesWithDataHandler needs fully initialized TYPO3', 1477066610);
        }
        $user = clone $GLOBALS['BE_USER'];
        $user->admin = 1;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [], $user);

        return $dataHandler;
    }
}
