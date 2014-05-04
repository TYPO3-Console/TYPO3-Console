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

use Helhum\Typo3Console\Mvc\Cli\RequestHandler;

/**
 * Class Package
 */
class Package extends \TYPO3\CMS\Core\Package\Package {

	public function boot(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
		parent::boot($bootstrap);
		require __DIR__ . '/../../../../typo3/sysext/extbase/Classes/Mvc/RequestHandlerInterface.php';
		require __DIR__ . '/Mvc/Cli/RequestHandler.php';
		$bootstrap->registerRequestHandler(new RequestHandler($bootstrap));
	}

} 