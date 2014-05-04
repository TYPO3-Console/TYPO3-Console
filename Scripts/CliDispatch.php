<?php

if (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === 'legacy') {
	$argv0 = './typo3/cli_dispatch.phpsh';
	$pwd = __DIR__ . '/../../../../typo3';
	$_SERVER['PHP_SELF'] = $pwd;
	chdir($pwd);
	$_SERVER['PHP_SELF'] =
	$_SERVER['PATH_TRANSLATED'] =
	$_SERVER['SCRIPT_FILENAME'] =
	$_SERVER['SCRIPT_NAME'] = $argv0;
	$_SERVER['argv'] = array_slice($_SERVER['argv'], 2);
	array_unshift($_SERVER['argv'], $argv0);

	$__pathPart = 'typo3/';
} else {
	$argv0 = array_shift($_SERVER['argv']);
	array_unshift($_SERVER['argv'], $argv0, 'extbase');

	$__pathPart = '';
}


// Starting from here this is basically a copy of typo3/cli_dispatch.phpsh
define('TYPO3_MODE', 'BE');
define('TYPO3_cliMode', TRUE);

require __DIR__ . '/../../../../typo3/sysext/core/Classes/Core/CliBootstrap.php';
\TYPO3\CMS\Core\Core\CliBootstrap::checkEnvironmentOrDie();

require __DIR__ . '/../../../../typo3/sysext/core/Classes/Core/Bootstrap.php';
\TYPO3\CMS\Core\Core\Bootstrap::getInstance()
	->baseSetup($__pathPart);

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

\TYPO3\CMS\Core\Core\CliBootstrap::initializeCliKeyOrDie();

\TYPO3\CMS\Core\Core\Bootstrap::getInstance()
	->loadExtensionTables(TRUE)
	->initializeBackendUser()
	->initializeBackendAuthentication()
	->initializeBackendUserMounts()
	->initializeLanguageObject();

// Make sure output is not buffered, so command-line output and interaction can take place
\TYPO3\CMS\Core\Utility\GeneralUtility::flushOutputBuffers();


try {
	include(TYPO3_cliInclude);
} catch (\Exception $e) {
	fwrite(STDERR, $e->getMessage() . LF);
	exit(99);
}

\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->shutdown();
 