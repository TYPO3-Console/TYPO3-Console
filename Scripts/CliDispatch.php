<?php

// Starting from here this is basically a copy of typo3/cli_dispatch.phpsh
define('TYPO3_MODE', 'BE');
define('TYPO3_cliMode', TRUE);

require __DIR__ . '/../../../../typo3/sysext/core/Classes/Core/CliBootstrap.php';
\TYPO3\CMS\Core\Core\CliBootstrap::checkEnvironmentOrDie();

require __DIR__ . '/../../../../typo3/sysext/core/Classes/Core/Bootstrap.php';
\TYPO3\CMS\Core\Core\Bootstrap::getInstance()
	->baseSetup('typo3/');

error_reporting(E_ALL & ~(E_STRICT | E_NOTICE));

\TYPO3\CMS\Core\Core\Bootstrap::getInstance()
	->loadConfigurationAndInitialize()
	->loadTypo3LoadedExtAndExtLocalconf(TRUE);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'] = '';
$errorHandler = new \Helhum\Typo3Console\Error\ErrorHandler();
$errorHandler->setExceptionalErrors(array(E_WARNING, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_STRICT, E_RECOVERABLE_ERROR));
ini_set('display_errors', 1);
if (((bool)ini_get('display_errors') && strtolower(ini_get('display_errors')) !== 'on' && strtolower(ini_get('display_errors')) !== '1') || !(bool)ini_get('display_errors')) {
	echo 'WARNING: Fatal errors will be suppressed due to your PHP config. You should consider enabling display_errors in your php.ini file!' . chr(10);
}

\TYPO3\CMS\Core\Core\Bootstrap::getInstance()
	->applyAdditionalConfigurationSettings()
	->initializeTypo3DbGlobal();

unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['extbase']);

\TYPO3\CMS\Core\Core\CliBootstrap::initializeCliKeyOrDie();

\TYPO3\CMS\Core\Core\Bootstrap::getInstance()
	->loadExtensionTables(TRUE)
	->initializeBackendUser()
	->initializeBackendAuthentication()
	->initializeLanguageObject();
	if (method_exists(\TYPO3\CMS\Core\Core\Bootstrap::getInstance(), 'initializeBackendUserMounts')) {
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeBackendUserMounts();
	}

// Make sure output is not buffered, so command-line output and interaction can take place
\TYPO3\CMS\Core\Utility\GeneralUtility::flushOutputBuffers();


try {
	include(TYPO3_cliInclude);
} catch (\Exception $e) {
	fwrite(STDERR, $e->getMessage() . LF);
	exit(99);
}

\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->shutdown();
 