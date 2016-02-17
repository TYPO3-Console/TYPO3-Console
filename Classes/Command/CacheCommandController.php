<?php
namespace Helhum\Typo3Console\Command;

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
        try {
            $this->cacheService->flush($force);

            // TODO: use nicer API once available
            ConsoleBootstrap::getInstance()->requestRunLevel(RunLevel::LEVEL_FULL);

            // Flush a second time to have extension caches and previously disabled core caches cleared when clearing not forced
            $this->cacheService->flush();

            $this->outputLine('Flushed all caches.');
        } catch (\Exception $e) {
            $this->outputLine($e->getMessage());
            $this->sendAndExit(1);
        }
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
     * Warmup essential caches such as class and core caches
     */
    public function warmupCommand()
    {
        if ($this->cacheService->warmupEssentialCaches()) {
            $this->outputLine('Warmed up the following caches: classes, package manager, tca, ext_tables, ext_localconf');
        } else {
            $this->outputLine('<info>Warmup skipped due to lack of classes cache</info>');
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
