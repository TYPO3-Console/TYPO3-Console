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

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Symfony\Component\Process\PhpProcess;
use TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\Response;

/**
 * Class SchedulerCommandController
 */
class FrontendCommandController extends CommandController {

	/**
	 * Submit a frontend request
	 *
	 * @param string $requestUrl URL to make a frontend request
	 */
	public function requestCommand($requestUrl) {

		// TODO: this needs heavy cleanup!
		$template = file_get_contents(PATH_typo3 . 'sysext/core/Tests/Functional/Fixtures/Frontend/request.tpl');
		$arguments = array(
			'documentRoot' => PATH_site,
			'requestUrl' => $requestUrl,
		);
		// No other solution atm than to fake a CLI request type
		$code = '<?php
		define(\'TYPO3_REQUESTTYPE\', 6);
		?>';
		$code .= str_replace(array('{originalRoot}', '{arguments}'), array(PATH_site, var_export($arguments, true)), $template);
		$process = new PhpProcess($code);
		$process->mustRun();
		$rawResponse = json_decode($process->getOutput());
		if ($rawResponse === NULL || $rawResponse->status === Response::STATUS_Failure) {
			$this->outputLine('<error>An error occured while trying to request the specified URL.</error>');
			$this->outputLine(sprintf('<error>Error: %s</error>', !empty($rawResponse->error) ? $rawResponse->error : 'Could not decode response. Please check your error log!'));
			$this->outputLine(sprintf('<error>Content: %s</error>', $process->getOutput()));
			$this->sendAndExit(1);
		}

		$this->output($rawResponse->content);
	}
}