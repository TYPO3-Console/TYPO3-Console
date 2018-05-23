<?php
defined('TYPO3_MODE') or die('Access denied.');

(function () {
    $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
    $signalSlotDispatcher->connect(
        \TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class,
        'afterExtensionInstall',
        \Helhum\Typo3Console\Hook\ExtensionInstallation::class,
        'afterInstallation'
    );
})();
