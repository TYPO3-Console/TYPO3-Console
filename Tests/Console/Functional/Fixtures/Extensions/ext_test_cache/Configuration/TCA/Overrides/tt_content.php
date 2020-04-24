<?php
declare(strict_types=1);
defined('TYPO3_MODE') or die();

$cacheManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
$cacheManager->getCache('cache_rootline')->get('foo');
