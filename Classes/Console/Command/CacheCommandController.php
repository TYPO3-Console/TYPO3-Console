<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command;

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

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Helhum\Typo3Console\Service\CacheService;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;

/**
 * CommandController for flushing caches
 */
class CacheCommandController extends CommandController
{
    /**
     * @var CacheService
     */
    private $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Flush all caches in specified groups
     *
     * Flushes all caches in specified groups.
     * Valid group names are by default:
     *
     * - lowlevel
     * - pages
     * - system
     *
     * <b>Example:</b> <code>%command.full_name% pages,all</code>
     *
     * @param array $groups An array of names (specified as comma separated values) of cache groups to flush
     */
    public function flushGroupsCommand(array $groups)
    {
        try {
            $this->cacheService->flushGroups($groups);
            $this->outputLine('Flushed all caches for group(s): "' . implode('","', $groups) . '".');
        } catch (NoSuchCacheGroupException $e) {
            $this->outputLine($e->getMessage());
            $this->quit(1);
        }
    }

    /**
     * Flush cache by tags
     *
     * Flushes caches by tags, optionally only caches in specified groups.
     *
     * <b>Example:</b> <code>%command.full_name% news_123 --groups pages,all</code>
     *
     * @param array $tags Array of tags (specified as comma separated values) to flush.
     * @param array $groups Optional array of groups (specified as comma separated values) for which to flush tags. If no group is specified, caches of all groups are flushed.
     */
    public function flushTagsCommand(array $tags, array $groups = null)
    {
        try {
            $this->cacheService->flushByTagsAndGroups($tags, $groups);
            if ($groups === null) {
                $this->outputLine('Flushed caches by tags "' . implode('","', $tags) . '".');
            } else {
                $this->outputLine('Flushed caches by tags "' . implode('","', $tags) . '" in groups: "' . implode('","', $groups) . '".');
            }
        } catch (NoSuchCacheGroupException $e) {
            $this->outputLine($e->getMessage());
            $this->quit(1);
        }
    }

    /**
     * List cache groups
     *
     * Lists all registered cache groups.
     */
    public function listGroupsCommand()
    {
        $groups = $this->cacheService->getValidCacheGroups();
        sort($groups);

        switch (count($groups)) {
            case 0:
                $this->outputLine('No cache groups are registered.');
                break;
            case 1:
                $this->outputLine('The following cache group is registered: "' . implode('", "', $groups) . '".');
                break;
            default:
                $this->outputLine('The following cache groups are registered: "' . implode('", "', $groups) . '".');
                break;
        }
    }
}
