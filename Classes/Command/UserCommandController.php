<?php
namespace Helhum\Typo3Console\Command;

/*
 * This file is part of the TYPO3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Helhum\Typo3Console\Mvc\Controller\CommandController;

/**
 * Commands for Backend users
 */
class UserCommandController extends CommandController {

	/**
	 * Create backend admin user
	 *
	 * @param string $username The username
	 * @param string $password The password
	 * @param string $realName Full Name
	 * @param string $email E-Mail
	 *
	 */
	public function createadminCommand($username, $password, $realName = '', $email = '') {
		$userExists = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'*',
			'be_users',
			'username=\'' . $username . '\''
		);

		if ($userExists === FALSE) {
			$this->outputLine('<error>Could not lookup existing users</error>');
			$this->sendAndExit(2);
		}

		$saltFactory = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(null, 'BE');
		$fields = array(
			'username' => $username,
			'password' => $saltFactory->getHashedPassword($password),
			'realName' => $realName,
			'email' => $email,
			'admin' => 1,
			'disable' => 0,
			'deleted' => 0,
			'tstamp' => time()
		);

		if ($userExists < 1) {
			$GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'be_users',
				$fields
			);
			$this->outputLine('<info>Backend admin created.</info>');
		} else {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'be_users',
				'username=\'' . $username . '\'',
				$fields
			);
			$this->outputLine('<warning>Backend user already exists - updated and enabled him</warning>');
		}
	}
}
