<?php
defined('TYPO3_MODE') or die();

$cacheManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
$cacheManager->getCache('cache_rootline')->get('foo');
$cacheManager->getCache('extbase_datamapfactory_datamap')->get('foo');
