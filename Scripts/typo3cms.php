<?php

call_user_func(function($scriptLocation) {
	// Cleanup pollution
	unset($GLOBALS['__self_dir']);

	/**
	 * Find out web path by trying several strategies
	 */
	if (getenv('TYPO3_PATH_WEB')) {
		$webRoot = getenv('TYPO3_PATH_WEB');
	} elseif (isset($scriptLocation) && file_exists($scriptLocation . '/typo3/sysext')) {
		$webRoot = $scriptLocation;
	} elseif (file_exists(getcwd() . '/typo3/sysext')) {
		$webRoot = getcwd();
	} else {
		// Assume we are located in typo3conf/ext and neither folder is a link
		$webRoot = realpath(__DIR__ . '/../../../../');
	}
	define('PATH_site', strtr($webRoot, '\\', '/') . '/');

	if (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === 'legacy') {
		// Legacy handling is subject to be removed ...
		$argv0 = './typo3/cli_dispatch.phpsh';
		$pwd = PATH_site . 'typo3';
		$_SERVER['PHP_SELF'] = $pwd;
		$_SERVER['PHP_SELF'] =
		$_SERVER['PATH_TRANSLATED'] =
		$_SERVER['SCRIPT_FILENAME'] =
		$_SERVER['SCRIPT_NAME'] = $argv0;
		$_SERVER['argv'] = array_slice($_SERVER['argv'], 2);
		array_unshift($_SERVER['argv'], $argv0);
		define('PATH_thisScript', realpath(PATH_site . $argv0));
		chdir(PATH_site);
		require __DIR__ . '/../Scripts/CliDispatch.php';
		exit(0);
	} else {
		define('PATH_thisScript', realpath(PATH_site . 'typo3cms'));
	}

	require PATH_site . 'typo3/sysext/core/Classes/Core/Bootstrap.php';
	require __DIR__ . '/../Classes/Core/ConsoleBootstrap.php';
	require PATH_site . 'typo3/sysext/core/Classes/Core/ApplicationContext.php';

	$context = getenv('TYPO3_CONTEXT') ?: (getenv('REDIRECT_TYPO3_CONTEXT') ?: 'Production');
	$bootstrap = new \Helhum\Typo3Console\Core\ConsoleBootstrap($context);
	$bootstrap->run();

}, (isset($__self_dir) ? $__self_dir : NULL));
