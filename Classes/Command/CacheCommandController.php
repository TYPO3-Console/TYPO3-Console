<?php
namespace Helhum\Typo3Console\Command;

/*
 * This file is part of the TYPO3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Core\ConsoleBootstrap;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;

/**
 * CommandController for flushing caches
 */
class CacheCommandController extends CommandController
{
    /**
     * @var \Helhum\Typo3Console\Service\CacheService
     * @inject
     */
    protected $cacheService;

    /**
     * Flush all caches
     *
     * Flushes TYPO3 core caches first and after that, flushes caches from extensions.
     *
     * @param bool $force Cache is forcibly flushed (low level operations are performed)
     */
    public function flushCommand($force = false)
    {
        $this->cacheService->flush($force);

        // TODO: use nicer API once available
        ConsoleBootstrap::getInstance()->requestRunLevel(RunLevel::LEVEL_FULL);

        // Flush a second time to have extension caches and previously disabled core caches cleared when clearing not forced
        $this->cacheService->flush();
        // Also call the data handler API to cover legacy hook subscriber code
        $this->cacheService->flushCachesWithDataHandler();

        $this->outputLine(sprintf('%slushed all caches.', $force ? 'Force f' : 'F'));
    }

    /**
     * Flush all caches in specified groups
     *
     * Flushes all caches in specified groups.
     * Valid group names are by default:
     *
     * - all
     * - lowlevel
     * - pages
     * - system
     *
     * <b>Example:</b> <code>./typo3cms cache:groups pages,all</code>
     *
     * @param array $groups An array of names (specified as comma separated values) of cache groups to flush
     */
    public function flushGroupsCommand(array $groups)
    {
        try {
            $this->cacheService->flushGroups($groups);
            $this->outputLine('Flushed all caches for group(s): "' . implode('","', $groups) . '"');
        } catch (NoSuchCacheGroupException $e) {
            $this->outputLine($e->getMessage());
            $this->sendAndExit(1);
        }
    }

    /**
     * Flush cache by tags
     *
     * Flushes caches by tags, optionally only caches in specified groups.
     *
     * <b>Example:</b> <code>./typo3cms cache:flushtags news_123 pages,all</code>
     *
     * @param array $tags Array of tags (specified as comma separated values) to flush.
     * @param array $groups Optional array of groups (specified as comma separated values) for which to flush tags. If no group is specified, caches of all groups are flushed.
     */
    public function flushTagsCommand(array $tags, array $groups = null)
    {
        try {
            $this->cacheService->flushByTagsAndGroups($tags, $groups);
            if ($groups === null) {
                $this->outputLine('Flushed caches by tags "' . implode('","', $tags) . '"');
            } else {
                $this->outputLine('Flushed caches by tags "' . implode('","', $tags) . '" in groups: "' . implode('","', $groups) . '"');
            }
        } catch (NoSuchCacheGroupException $e) {
            $this->outputLine($e->getMessage());
            $this->sendAndExit(1);
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
                $this->outputLine('No cache group is registered.');
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
