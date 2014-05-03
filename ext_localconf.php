<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['SchedulerCommandController'] = 'Helhum\\Typo3Console\\Command\\SchedulerCommandController';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['BackendCommandController'] = 'Helhum\\Typo3Console\\Command\\BackendCommandController';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['CleanupCommandController'] = 'Helhum\\Typo3Console\\Command\\CleanupCommandController';
}
