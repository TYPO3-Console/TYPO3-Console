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

use Helhum\Typo3Console\Parser\ParsingException;
use Helhum\Typo3Console\Parser\PhpParser;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * CommandController for flushing caches through CLI/scheduler
 *
 */
class CacheCommandController extends CommandController {

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
	 * @var
	 */
	protected $databaseConnection;

	/**
	 * Flushes all caches, optionally only caches in specified groups.
	 *
	 * @param array $groups
	 */
	public function flushCommand(array $groups = NULL) {
		if ($groups === NULL) {
			$this->cacheManager->flushCaches();
			$this->outputLine('Flushed all caches.');
		} else {
			$flushedGroups = array();
			$invalidGroups = array();
			foreach ($groups as $group) {
				try {
					$this->cacheManager->flushCachesInGroup($group);
					$flushedGroups[] = $group;
				} catch (NoSuchCacheGroupException $e) {
					$invalidGroups[] = $group;
				}
			}
			if (!empty($flushedGroups)) {
				$this->outputLine('Flushed all caches for group(s): "' . implode('","', $flushedGroups) . '"');
			}
			if (!empty($invalidGroups)) {
				$this->outputLine('The following invalid groups were ignored: "' . implode('","', $invalidGroups) . '"');
			}
		}
	}

	/**
	 * Flushes caches by tags, optionally only caches in specified groups.
	 *
	 * @param array $tags
	 * @param array $groups
	 */
	public function flushByTagCommand(array $tags, array $groups = NULL) {
		foreach ($tags as $tag) {
			if ($groups === NULL) {
				$this->cacheManager->flushCachesByTag($tag);
				$this->outputLine('Flushed all caches by tag "' . $tag . '"');
			} else {
				$flushedGroups = array();
				$invalidGroups = array();
				foreach ($groups as $group) {
					try {
						$this->cacheManager->flushCachesInGroupByTag($group, $tag);
						$flushedGroups[] = $group;
					} catch (NoSuchCacheGroupException $e) {
						$invalidGroups[] = $group;
					}
				}
				if (!empty($flushedGroups)) {
					$this->outputLine('Cleared cache tag "' . $tag . '" for groups: "' . implode('","', $flushedGroups) . '"');
				}
				if (!empty($invalidGroups)) {
					$this->outputLine('The following invalid groups were ignored: "' . implode('","', $invalidGroups) . '"');
				}
			}
		}
	}

	/**
	 * Warmup class and core caches
	 */
	public function warmupCommand() {
		$phpParser = new PhpParser();
		foreach ($this->packageManager->getActivePackages() as $package) {
			$classFiles = GeneralUtility::getAllFilesAndFoldersInPath(array(), $package->getClassesPath(), 'php');
			foreach ($classFiles as $classFile) {
				try {
					$parsedResult = $phpParser->parseClassFile($classFile);
					class_exists($parsedResult->getFullyQualifiedClassName());
				} catch(ParsingException $e) {
					$this->outputLine('Class file "' . PathUtility::stripPathSitePrefix($classFile) . '" does not contain a class defininition. Skipping …');
				}
			}
		}
	}
}
