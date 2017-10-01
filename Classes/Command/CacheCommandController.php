<?php
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

use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
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

    /**
     * @var CommandDispatcher
     */
    private $commandDispatcher;

    /**
     * @param CacheService $cacheService
     * @param CommandDispatcher $commandDispatcher
     */
    public function __construct(CacheService $cacheService, CommandDispatcher $commandDispatcher = null)
    {
        $this->cacheService = $cacheService;
        $this->commandDispatcher = $commandDispatcher ?: CommandDispatcher::createFromCommandRun();
    }

    /**
     * Flush all caches
     *
     * Flushes TYPO3 core caches first and after that, flushes caches from extensions.
     *
     * @param bool $force Cache is forcibly flushed (low level operations are performed)
     * @param bool $filesOnly Only file caches are flushed
     * @throws \Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException
     */
    public function flushCommand($force = false, $filesOnly = false)
    {
        $exitCode = 0;
        if (!$filesOnly) {
            try {
                $this->cacheService->flush($force);
                $this->commandDispatcher->executeCommand('cache:flushcomplete');
            } catch (\Throwable $e) {
                $exitCode = 1;
                $filesOnly = true;
            } catch (\Exception $e) {
                // @deprecated can be removed once PHP 5 / TYPO3 7.6 support is removed
                $exitCode = 1;
                $filesOnly = true;
            }
        }
        if ($filesOnly) {
            $this->cacheService->flushFileCaches($force);
            $this->commandDispatcher->executeCommand('cache:flushcomplete', ['--files-only' => true]);
        }

        if (isset($e)) {
            $this->outputLine('<error>Flushing caches failed with error:</error>');
            $this->outputLine('<error>"%s"</error>', [$e->getMessage()]);
            $this->outputLine('<warning>Falling back to flushing file caches only.</warning>');
            $this->outputLine('<warning>Use "--files-only" option to get rid of this warning"</warning>');
        }

        $this->outputLine('%slushed all %scaches.', [$force ? 'Force f' : 'F', $filesOnly ? 'file ' : '']);
        $this->quit($exitCode);
    }

    /**
     * Called only internally in a sub process of the cache:flush command
     *
     * This command will then use the full TYPO3 bootstrap.
     *
     * @param bool $filesOnly Only file caches are flushed
     * @internal
     */
    public function flushCompleteCommand($filesOnly = false)
    {
        // Flush a second time to have extension caches and previously disabled core caches cleared when clearing not forced
        if ($filesOnly) {
            $this->cacheService->flushFileCaches();
        } else {
            $this->cacheService->flush();
            // Also call the data handler API to cover legacy hook subscriber code
            $this->cacheService->flushCachesWithDataHandler();
        }
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
     * <b>Example:</b> <code>typo3cms cache:flushgroups pages,all</code>
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
            $this->sendAndExit(1);
        }
    }

    /**
     * Flush cache by tags
     *
     * Flushes caches by tags, optionally only caches in specified groups.
     *
     * <b>Example:</b> <code>typo3cms cache:flushtags news_123 pages,all</code>
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
