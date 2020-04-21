<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['normalWizard']
    = \Helhum\Typo3Console\Tests\Functional\Fixtures\Extensions\ext_upgrade\src\NormalUpgradeWizard::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['repeatableWizard']
    = \Helhum\Typo3Console\Tests\Functional\Fixtures\Extensions\ext_upgrade\src\RepeatableUpgradeWizard::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['confirmableWizard']
    = \Helhum\Typo3Console\Tests\Functional\Fixtures\Extensions\ext_upgrade\src\ConfirmableUpgradeWizard::class;
