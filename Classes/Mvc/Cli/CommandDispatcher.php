<?php
namespace Helhum\Typo3Console\Mvc\Cli;

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

/**
 * Class Disptacher
 */
class CommandDispatcher {

	/**
	 * @param string $commandIdentifier
	 * @param array $arguments
	 * @return string Json encoded output of the executed command
	 * @throws \Exception
	 */
	public function executeCommand($commandIdentifier, $arguments = array()) {
		$phpBinary = defined('PHP_BINARY') ? PHP_BINARY : (!empty($_SERVER['_']) ? $_SERVER['_'] : '');
		$commandLine = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();
		$callingScript = array_shift($commandLine);
		$commandLineArguments = array();
		$commandLineArguments[] = $commandIdentifier;

		foreach ($arguments as $argumentName => $argumentValue) {
			$dashedName = ucfirst($argumentName);
			$dashedName = preg_replace('/([A-Z][a-z0-9]+)/', '$1-', $dashedName);
			$dashedName = '--' . strtolower(substr($dashedName, 0, -1));
			$commandLineArguments[] = $dashedName;
			$commandLineArguments[] = $argumentValue;
		}

		$scriptToExecute = (!empty($phpBinary) ? (escapeshellcmd($phpBinary) . ' ') : '') . escapeshellcmd($callingScript) . ' ' . implode(' ', array_map('escapeshellarg', $commandLineArguments));
		$returnString = exec($scriptToExecute, $output, $returnValue);
		if ($returnValue > 0) {
			throw new \ErrorException(sprintf('Executing %s failed with message: ', $commandIdentifier) . LF . implode(LF, $output));
		}
		return $returnString;
	}
}