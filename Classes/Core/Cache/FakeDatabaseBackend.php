<?php
namespace Helhum\Typo3Console\Core\Cache;

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

use TYPO3\CMS\Core\Cache\Backend\AbstractBackend;
use TYPO3\CMS\Core\Cache\Backend\PhpCapableBackendInterface;
use TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * A caching backend which forgets everything immediately,
 * but pretends to be a database backend
 *
 */
class FakeDatabaseBackend extends AbstractBackend implements PhpCapableBackendInterface, TaggableBackendInterface
{
    /**
     * @var string Presumably name of the cache data table
     */
    protected $cacheTable;

    /**
     * @var string Presumably name of the cache tags table
     */
    protected $tagsTable;

    /**
     * Set cache frontend instance and calculate data and tags table name
     *
     * @param FrontendInterface $cache The frontend for this backend
     * @return void
     * @api
     */
    public function setCache(FrontendInterface $cache)
    {
        parent::setCache($cache);
        $this->cacheTable = 'cf_' . $this->cacheIdentifier;
        $this->tagsTable = 'cf_' . $this->cacheIdentifier . '_tags';
    }

    /**
     * Acts as if it would save data
     *
     * @param string $entryIdentifier ignored
     * @param string $data ignored
     * @param array $tags ignored
     * @param int $lifetime ignored
     * @return void
     * @api
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
    }

    /**
     * Acts as if it would enable data compression
     *
     * @param bool $compression ignored
     * @return void
     */
    public function setCompression($compression)
    {
    }

    /**
     * Returns False
     *
     * @param string $entryIdentifier ignored
     * @return bool FALSE
     * @api
     */
    public function get($entryIdentifier)
    {
        return false;
    }

    /**
     * Returns False
     *
     * @param string $entryIdentifier ignored
     * @return bool FALSE
     * @api
     */
    public function has($entryIdentifier)
    {
        return false;
    }

    /**
     * Does nothing
     *
     * @param string $entryIdentifier ignored
     * @return bool FALSE
     * @api
     */
    public function remove($entryIdentifier)
    {
        return false;
    }

    /**
     * Returns an empty array
     *
     * @param string $tag ignored
     * @return array An empty array
     * @api
     */
    public function findIdentifiersByTag($tag)
    {
        return [];
    }

    /**
     * Does nothing
     *
     * @return void
     * @api
     */
    public function flush()
    {
    }

    /**
     * Does nothing
     *
     * @param string $tag ignored
     * @return void
     * @api
     */
    public function flushByTag($tag)
    {
    }

    /**
     * Does nothing
     *
     * @return void
     * @api
     */
    public function collectGarbage()
    {
    }

    /**
     * Does nothing
     *
     * @param string $identifier An identifier which describes the cache entry to load
     * @return void
     * @api
     */
    public function requireOnce($identifier)
    {
    }

    /**
     * Calculate needed table definitions for this cache.
     * This helper method is used by install tool and extension manager
     * and is not part of the public API!
     *
     * @return string SQL of table definitions
     */
    public function getTableDefinitions()
    {
        $cacheTableSql = file_get_contents(
            ExtensionManagementUtility::extPath('core') .
            'Resources/Private/Sql/Cache/Backend/Typo3DatabaseBackendCache.sql'
        );
        $requiredTableStructures = str_replace('###CACHE_TABLE###', $this->cacheTable, $cacheTableSql) . LF . LF;
        $tagsTableSql = file_get_contents(
            ExtensionManagementUtility::extPath('core') .
            'Resources/Private/Sql/Cache/Backend/Typo3DatabaseBackendTags.sql'
        );
        $requiredTableStructures .= str_replace('###TAGS_TABLE###', $this->tagsTable, $tagsTableSql) . LF;
        return $requiredTableStructures;
    }
}
