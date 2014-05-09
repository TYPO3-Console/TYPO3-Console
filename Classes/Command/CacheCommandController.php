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

use Helhum\Typo3Console\Service;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * CommandController for flushing caches through CLI/scheduler
 *
 */
class CacheCommandController extends CommandController {

	/**
	 * @var \Helhum\Typo3Console\Service\CacheService
	 * @inject
	 */
	protected $cacheService;

	/**
	 * Flushes all caches, optionally only caches in specified groups.
	 *
	 * @param array $groups
	 */
	public function flushCommand(array $groups = NULL) {
		try {
			$this->cacheService->flush($groups);
			if (empty($groups)) {
				$this->outputLine('Flushed all caches.');
			} else {
				$this->outputLine('Flushed all caches for group(s): "' . implode('","', $groups) . '"');
			}
		} catch (NoSuchCacheGroupException $e) {
			$this->outputLine($e->getMessage());
		}
	}

	/**
	 * Flushes caches by tags, optionally only caches in specified groups.
	 *
	 * @param array $tags
	 * @param array $groups
	 */
	public function flushByTagsCommand(array $tags, array $groups = NULL) {
		try {
			$this->cacheService->flushByTagsAndGroups($tags, $groups);
			$this->outputLine('Flushed caches by tags "' . implode('","', $tags) . '" in groups: "' . implode('","', $groups) . '"');
		} catch (NoSuchCacheGroupException $e) {
			$this->outputLine($e->getMessage());
		}
	}

	/**
	 * Warmup essential caches such as class and core caches
	 */
	public function warmupCommand() {
		$this->cacheService->warmupEssentialCaches();
	}
}
