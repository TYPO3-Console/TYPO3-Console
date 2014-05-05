<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter('Helhum\\Typo3Console\\Property\\TypeConverter\\ArrayConverter');

if (TYPO3_MODE === 'BE') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['SchedulerCommandController'] = 'Helhum\\Typo3Console\\Command\\SchedulerCommandController';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['CleanupCommandController'] = 'Helhum\\Typo3Console\\Command\\CleanupCommandController';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'Helhum\\Typo3Console\\Command\\CacheCommandController';
}
