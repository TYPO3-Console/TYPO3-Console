<?php
defined('TYPO3_MODE') or die('Access denied.');

if (defined('TYPO3_cliMode') && TYPO3_cliMode) {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter('Helhum\\Typo3Console\\Property\\TypeConverter\\ArrayConverter');
}

