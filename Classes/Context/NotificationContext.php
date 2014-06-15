<?php
namespace Helhum\Typo3Console\Context;

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

use Helhum\Typo3Console\Event\EventEmitterTrait;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\NullWriter;

/**
 * Class NotificationContext
 */
class NotificationContext {

	use EventEmitterTrait;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	function __construct(LoggerInterface $logger = NULL) {
		$this->logger = $logger ?:  $this->createNullLogger();
	}

	/**
	 * @return \Psr\Log\LoggerInterface
	 */
	public function getLogger() {
		return $this->logger;
	}

	protected function createNullLogger() {
		$logger = new Logger(__CLASS__);
		$logger->addWriter(LogLevel::EMERGENCY, new NullWriter());
		return $logger;
	}
}