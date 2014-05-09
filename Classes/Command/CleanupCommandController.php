<?php
namespace Helhum\Typo3Console\Command;

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

use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class CleanupCommandController
 */
class CleanupCommandController extends CommandController {

	/**
	 * @var ReferenceIndex
	 */
	protected $referenceIndex;

	/**
	 *
	 */
	public function initializeObject() {
		$this->referenceIndex = $this->objectManager->get('TYPO3\\CMS\\Core\\Database\\ReferenceIndex');
	}

	/**
	 * Checks if integrity of reference index
	 *
	 * @param bool $verbose Whether to output results or not
	 */
	public function checkReferenceIndexCommand($verbose = TRUE) {
		$this->outputLine('Checking reference index …');
		list($header, $main, $errorCount) = $this->referenceIndex->updateIndex(TRUE, FALSE);
		if ($verbose) {
			$this->output($main . LF);
		}
		if ($errorCount > 0) {
			$this->outputLine('The reference index check found %s errors.', array($errorCount));
		}
	}

	/**
	 * Updates reference index to ensure integrity
	 *
	 * @param bool $verbose Whether to output results or not
	 */
	public function updateReferenceIndexCommand($verbose = TRUE) {
		$this->outputLine('Updating reference index …');
		list($header, $main, $errorCount) = $this->referenceIndex->updateIndex(FALSE, FALSE);
		if ($verbose) {
			$this->output($main . LF);
		}
		if ($errorCount > 0) {
			$this->outputLine('The reference index update process found %s errors.', array($errorCount));
		}
	}
}