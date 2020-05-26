<?php
defined('TYPO3_MODE') or die();

if (empty($GLOBALS['TCA']['tx_exttest_cattest'])) {
    throw new \RuntimeException('TCA not loaded before ext_tables.php', 1590494014);
}
