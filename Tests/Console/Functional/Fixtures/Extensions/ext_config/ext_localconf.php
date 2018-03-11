<?php
defined('TYPO3_MODE') or die();
if (class_exists(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)) {
    $extensionConfiguration = new \TYPO3\CMS\Core\Configuration\ExtensionConfiguration();
    $extensionConfiguration->get('ext_config', 'activateTest');
}
