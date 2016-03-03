<?php
namespace Helhum\Typo3Console\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Helhum\Typo3Console\Parser\ParsingException;
use Helhum\Typo3Console\Parser\PhpParser;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Service\OpcodeCacheService;

/**
 * Class CacheService
 * TODO: This is not really a service in DDD terms: it does not act on domain models, it has dependencies and it holds som kind of state (logger) find a better name/pattern for that.
 */
class CacheService implements SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Core\Cache\CacheManager
     * @inject
     */
    protected $cacheManager;

    /**
     * @var \TYPO3\CMS\Core\Package\PackageManager
     * @inject
     */
    protected $packageManager;

    /**
     * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
     * @inject
     */
    protected $configurationManager;

    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Fetches and sets the logger instance
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(__CLASS__);
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Flushes all caches
     *
     * @param bool $force
     */
    public function flush($force = false)
    {
        if ($force) {
            $this->forceFlushCoreFileAndDatabaseCaches();
        }
        $this->cacheManager->flushCaches();
        $this->flushOpcodeCache();
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
     * Flushes PHP opcode cache
     */
    public function flushOpcodeCache()
    {
        GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive();
    }

    /**
     * Warm up essential caches such as class and core caches
     *
     * @param bool $triggerRequire
     * @return bool
     */
    public function warmupEssentialCaches($triggerRequire = false)
    {
        try {
            $this->cacheManager->getCache('cache_classes');
        } catch (NoSuchCacheException $e) {
            $this->logger->warning('Warmup skipped due to lack of classes cache');
            return false;
        }
        // TODO: This currently only builds the classes cache! Find a way to build other system caches as well (like reflection caches, datamap caches …)
        // package namespace and aliases caches are implicitly built in extended bootstrap before we reach this point
        $phpParser = new PhpParser();
        foreach ($this->packageManager->getActivePackages() as $package) {
            $classFiles = GeneralUtility::getAllFilesAndFoldersInPath(array(), $package->getClassesPath(), 'php');
            foreach ($classFiles as $classFile) {
                try {
                    $parsedResult = $phpParser->parseClassFile($classFile);
                    $this->writeCacheEntryForClass($parsedResult->getFullyQualifiedClassName(), $classFile);
                } catch (ParsingException $e) {
                    $this->logger->warning('Class file "' . PathUtility::stripPathSitePrefix($classFile) . '" does not contain a class definition. Skipping …');
                }
            }
        }
        $this->packageManager->injectCoreCache($this->cacheManager->getCache('cache_core'));
        $this->packageManager->populatePackageCache();

        return true;
    }

    /**
     * @return array
     */
    public function getValidCacheGroups()
    {
        $validGroups = array();
        foreach ($this->configurationManager->getConfigurationValueByPath('SYS/caching/cacheConfigurations') as $cacheConfiguration) {
            if (isset($cacheConfiguration['groups']) && is_array($cacheConfiguration['groups'])) {
                $validGroups = array_merge($validGroups, $cacheConfiguration['groups']);
            }
        }
        return array_unique($validGroups);
    }

    /**
     * @param array $groups
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException
     */
    protected function ensureCacheGroupsExist($groups)
    {
        $validGroups = $this->getValidCacheGroups();
        $sanitizedGroups = array_intersect($groups, $validGroups);
        if (count($sanitizedGroups) !== count($groups)) {
            $invalidGroups = array_diff($groups, $sanitizedGroups);
            throw new NoSuchCacheGroupException('Invalid cache groups "' . implode(', ', $invalidGroups) . '"', 1399630162);
        }
    }

    /**
     * Recursively delete cache directory and truncate all DB tables prefixed with 'cf_'
     */
    protected function forceFlushCoreFileAndDatabaseCaches()
    {
        // Delete typo3temp/Cache
        GeneralUtility::rmdir(PATH_site . 'typo3temp/Cache', true);
        // Get all table names starting with 'cf_' and truncate them
        $tables = $this->databaseConnection->admin_get_tables();
        foreach ($tables as $table) {
            $tableName = $table['Name'];
            if (substr($tableName, 0, 3) === 'cf_' || ($tableName !== 'tx_realurl_redirects' && substr($tableName, 0, 11) === 'tx_realurl_')) {
                $this->databaseConnection->exec_TRUNCATEquery($tableName);
            }
        }
    }

    /**
     * @param string $classFile
     * @param string $className
     * @param bool $triggerRequire
     */
    protected function writeCacheEntryForClass($className, $classFile, $triggerRequire = false)
    {
        $classesCache = $this->cacheManager->getCache('cache_classes');
        $cacheEntryIdentifier = strtolower(str_replace('\\', '_', $className));
        $classLoadingInformation = array(
            $classFile,
            strtolower($className),
            // TODO: consider aliases?
        );
        if (!$classesCache->has($cacheEntryIdentifier)) {
            $classesCache->set(
                $cacheEntryIdentifier,
                implode("\xff", $classLoadingInformation)
            );
        }
    }
}
