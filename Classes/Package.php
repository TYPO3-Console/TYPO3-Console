<?php
namespace typo3_console;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Helhum\Typo3Console\Core\ConsoleBootstrap;
use Helhum\Typo3Console\Mvc\Cli\RequestHandler;

/**
 * Class Package
 */
class Package extends \TYPO3\CMS\Core\Package\Package {

	/**
	 * @var string
	 */
	protected $namespace = 'Helhum\\Typo3Console';

	/**
	 * Register the cli request handler only when in cli mode
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 */
	public function boot(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
		if (defined('TYPO3_cliMode') && TYPO3_cliMode && is_callable(array($bootstrap, 'registerRequestHandler'))) {
			parent::boot($bootstrap);
			require __DIR__ . '/../../../../typo3/sysext/extbase/Classes/Mvc/RequestHandlerInterface.php';
			require __DIR__ . '/Mvc/Cli/RequestHandler.php';
			$bootstrap->registerRequestHandler(new RequestHandler($bootstrap));
			$this->registerCommands($bootstrap);
		}
	}

	/**
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 */
	protected function registerCommands(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
		$bootstrap->registerCommandForRunLevel('typo3_console:cache:flush', ConsoleBootstrap::RUNLEVEL_COMPILE);
		$bootstrap->registerCommandForRunLevel('typo3_console:cache:flushbygroups', ConsoleBootstrap::RUNLEVEL_EXTENDED_RUNTIME);
		$bootstrap->registerCommandForRunLevel('typo3_console:cache:flushbytags', ConsoleBootstrap::RUNLEVEL_EXTENDED_RUNTIME);
		$bootstrap->registerCommandForRunLevel('typo3_console:cache:warmup', ConsoleBootstrap::RUNLEVEL_EXTENDED_RUNTIME);
		$bootstrap->registerCommandForRunLevel('typo3_console:backend:*', ConsoleBootstrap::RUNLEVEL_BASIC_RUNTIME);
		$bootstrap->registerCommandForRunLevel('typo3_console:scheduler:*', ConsoleBootstrap::RUNLEVEL_EXTENDED_RUNTIME);
		$bootstrap->registerCommandForRunLevel('typo3_console:cleanup:checkreferenceindex', ConsoleBootstrap::RUNLEVEL_EXTENDED_RUNTIME);
		$bootstrap->registerCommandForRunLevel('typo3_console:cleanup:updatereferenceindex', ConsoleBootstrap::RUNLEVEL_EXTENDED_RUNTIME);
		$bootstrap->registerCommandForRunLevel('typo3_console:documentation:generatexsd', ConsoleBootstrap::RUNLEVEL_EXTENDED_RUNTIME);
	}
}