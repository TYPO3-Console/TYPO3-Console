<?php
defined('TYPO3_MODE') or die();
$extensionConfiguration = new \TYPO3\CMS\Core\Configuration\ExtensionConfiguration();
$extensionConfiguration->get('ext_config', 'activateTest');
