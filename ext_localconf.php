<?php
defined('TYPO3_MODE') or die('Access denied.');

call_user_func(function($extensionKey) {
	if (defined('TYPO3_cliMode') && TYPO3_cliMode) {
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter('Helhum\\Typo3Console\\Property\\TypeConverter\\ArrayConverter');
	} elseif (TYPO3_MODE === 'BE' && isset($_GET['M']) && 'tools_ExtensionmanagerExtensionmanager' === $_GET['M']) {
		$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
		$signalSlotDispatcher->connect(
			'TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService',
			'hasInstalledExtensions',
			function($keyOfInstalledExtension) use ($extensionKey) {
				if ($extensionKey !== $keyOfInstalledExtension) {
					return;
				}
				\Helhum\Typo3Console\Composer\InstallerScripts::postInstallExtension();
			}
		);
	}
}, $_EXTKEY);

