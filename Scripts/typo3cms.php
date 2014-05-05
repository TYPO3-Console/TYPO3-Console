<?php

$__boot = function() {
	//require __DIR__ . '/../../../../typo3/sysext/core/Classes/Core/CliBootstrap.php';
	require __DIR__ . '/../../../../typo3/sysext/core/Classes/Core/Bootstrap.php';
	require __DIR__ . '/../../../../typo3/sysext/core/Classes/Core/ApplicationContext.php';
	require __DIR__ . '/../../../../typo3/sysext/core/Classes/Exception.php';
	require __DIR__ . '/../Classes/Core/ConsoleBootstrap.php';
	require __DIR__ . '/../Classes/Error/ErrorHandler.php';

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

		define('PATH_site', realpath(__DIR__ . '/../../../../') . '/');
		define('PATH_thisScript', realpath(__DIR__ . '/../../../../' . $argv0));
	} else {
	//	$argv0 = array_shift($_SERVER['argv']);
	//	array_unshift($_SERVER['argv'], $argv0, 'extbase');

		define('PATH_site', realpath(__DIR__ . '/../../../../') . '/');
		define('PATH_thisScript', realpath(__DIR__ . '/../../../../typo3cms'));
	}

	$context = getenv('TYPO3_CONTEXT') ?: (getenv('REDIRECT_TYPO3_CONTEXT') ?: 'Production');
	$bootstrap = new \Helhum\Typo3Console\Core\ConsoleBootstrap($context);
	$bootstrap->run();
};

$__boot();