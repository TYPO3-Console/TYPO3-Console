<?php
declare(strict_types=1);
defined('TYPO3_MODE') or die();
// @deprecated with TYPO3 12, this fixture can be removed
if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 12) {
    $cacheManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
    $cacheManager->getCache('cache_rootline')->get('foo');
}
