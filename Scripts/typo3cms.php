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
	define('PATH_thisScript', realpath(PATH_site . 'typo3/cli_dispatch.phpsh'));

	if (@file_exists(PATH_site . 'typo3/sysext/core/Classes/Core/ApplicationInterface.php')) {
		foreach (array(PATH_site . 'typo3/../vendor/autoload.php', PATH_site . 'typo3/vendor/autoload.php') as $possibleAutoloadLocation) {
			if (file_exists($possibleAutoloadLocation)) {
				$classLoader = require_once $possibleAutoloadLocation;
				break;
			}
		}
	} else {
		require_once PATH_site . 'typo3/sysext/core/Classes/Core/Bootstrap.php';
		require_once PATH_site . 'typo3/sysext/core/Classes/Core/ApplicationContext.php';
	}

	require __DIR__ . '/../Classes/Core/ConsoleBootstrap.php';
	$context = getenv('TYPO3_CONTEXT') ?: (getenv('REDIRECT_TYPO3_CONTEXT') ?: 'Production');
	$bootstrap = new \Helhum\Typo3Console\Core\ConsoleBootstrap($context);
	$bootstrap->run(isset($classLoader) ? $classLoader : NULL);

}, (isset($__self_dir) ? $__self_dir : NULL));
