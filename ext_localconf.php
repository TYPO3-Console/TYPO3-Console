<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter('Helhum\\Typo3Console\\Property\\TypeConverter\\ArrayConverter');

if (TYPO3_MODE === 'BE') {
}
