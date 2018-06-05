<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable(
    'ext_test',
    'tx_exttest_cattest'
);

if (empty($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['test'])) {
    $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['test'] = [];
}
