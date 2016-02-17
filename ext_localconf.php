<?php
defined('TYPO3_MODE') or die('Access denied.');

call_user_func(function ($extensionKey) {
    if ((TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI)) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter('Helhum\\Typo3Console\\Property\\TypeConverter\\ArrayConverter');
    } elseif (TYPO3_MODE === 'BE' && isset($_GET['M']) && 'tools_ExtensionmanagerExtensionmanager' === $_GET['M']) {
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
        $signalSlotDispatcher->connect(
            'TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService',
            'hasInstalledExtensions',
            'Helhum\\Typo3Console\\Hook\\ExtensionInstallation',
            'afterInstallation'
        );
    }
}, $_EXTKEY);
