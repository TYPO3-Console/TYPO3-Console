<?php
namespace Helhum\Typo3Console\Command;

/*
 * This file is part of the typo3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Core\ConsoleBootstrap;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Helhum\Typo3Console\Service;
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
     * Sets a custom logger for the service
     */
    protected function initializeObject()
    {
        $this->cacheService->setLogger($this->createDefaultLogger());
    }

    /**
     * Flushes all caches.
     * @param bool $force
     */
    public function flushCommand($force = false)
    {
        $this->cacheService->flush($force);

        // TODO: use nicer API once available
        ConsoleBootstrap::getInstance()->requestRunLevel(RunLevel::LEVEL_FULL);

        // Flush a second time to have extension caches and previously disabled core caches cleared when clearing not forced
        $this->cacheService->flush();

        $this->outputLine('Flushed all caches.');
    }

    /**
     * Flushes all caches in specified groups.
     *
     * @param array $groups
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
     * Flushes caches by tags, optionally only caches in specified groups.
     *
     * @param array $tags
     * @param array $groups
     */
    public function flushTagsCommand(array $tags, array $groups = null)
    {
        try {
            $this->cacheService->flushByTagsAndGroups($tags, $groups);
            $this->outputLine('Flushed caches by tags "' . implode('","', $tags) . '" in groups: "' . implode('","', $groups) . '"');
        } catch (NoSuchCacheGroupException $e) {
            $this->outputLine($e->getMessage());
            $this->sendAndExit(1);
        }
    }

    /**
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
