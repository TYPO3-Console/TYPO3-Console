<?php
defined('TYPO3_MODE') or die('Access denied.');

call_user_func(function () {
    if ((TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) || (TYPO3_MODE === 'BE' && isset($_GET['M']) && 'tools_ExtensionmanagerExtensionmanager' === $_GET['M'])) {
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
        $signalSlotDispatcher->connect(
            \TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class,
            'afterExtensionInstall',
            \Helhum\Typo3Console\Hook\ExtensionInstallation::class,
            'afterInstallation'
        );
    }
});
