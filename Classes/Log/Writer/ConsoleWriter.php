<?php
namespace Helhum\Typo3Console\Log\Writer;

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

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;

/**
 * Class ConsoleWriter
 */
class ConsoleWriter extends AbstractWriter {

	/**
	 * @var OutputInterface
	 */
	protected $output;

	/**
	 * @var string
	 */
	protected $messageWrap = '|';

	protected $severityTagMapping = array(
		LogLevel::EMERGENCY => '<error>|</error>',
		LogLevel::ALERT => '<error>|</error>',
		LogLevel::CRITICAL => '<error>|</error>',
		LogLevel::ERROR => '<fg=red>|</fg=red>',
		LogLevel::WARNING => '<fg=yellow;options=bold>|</fg=yellow;options=bold>',
		LogLevel::NOTICE => '<fg=yellow>|</fg=yellow>',
		LogLevel::INFO => '<info>|</info>',
		LogLevel::DEBUG => '|'
	);
	/**
	 * @param OutputInterface $output
	 */
	public function setOutput(OutputInterface $output) {
		$this->output = $output;
	}

	/**
	 * @param string $messageWrap
	 */
	public function setMessageWrap($messageWrap) {
		$this->messageWrap = $messageWrap;
	}

	/**
	 * Writes the log record
	 *
	 * @param \TYPO3\CMS\Core\Log\LogRecord $record Log record
	 * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface $this
	 * @throws \Exception
	 */
	public function writeLog(\TYPO3\CMS\Core\Log\LogRecord $record) {
		$this->output->write(
			$this->wrapMessage(vsprintf($record->getMessage(), $record->getData()), $record->getLevel()),
			TRUE
		);
	}

	protected function wrapMessage($message, $level) {
		list($tagStart, $tagEnd) = explode('|', $this->severityTagMapping[$level]);
		list($wrapStart, $wrapEnd) = explode('|', $this->messageWrap);
		return $tagStart . $wrapStart . $message . $wrapEnd . $tagEnd;
	}
}