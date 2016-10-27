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

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;

/**
 * A wrapper for Symfony ConsoleOutput and related helpers.
 */
class ConsoleOutput
{
    /**
     * @var SymfonyConsoleOutput
     */
    protected $output;

    /**
     * @var DialogHelper
     */
    protected $dialogHelper;

    /**
     * @var ProgressHelper
     */
    protected $progressHelper;

    /**
     * @var TableHelper
     */
    protected $tableHelper;

    /**
     * Creates and initializes the SymfonyConsoleOutput instance.
     */
    public function __construct()
    {
        $this->output = new SymfonyConsoleOutput();
        $this->output->getFormatter()->setStyle('b', new OutputFormatterStyle(null, null, ['bold']));
        $this->output->getFormatter()->setStyle('i', new OutputFormatterStyle('black', 'white'));
        $this->output->getFormatter()->setStyle('u', new OutputFormatterStyle(null, null, ['underscore']));
        $this->output->getFormatter()->setStyle('em', new OutputFormatterStyle(null, null, ['reverse']));
        $this->output->getFormatter()->setStyle('strike', new OutputFormatterStyle(null, null, ['conceal']));
        $this->output->getFormatter()->setStyle('error', new OutputFormatterStyle('red'));
        $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('green'));
    }

    /**
     * Returns the desired maximum line length for console output.
     *
     * @return int
     */
    public function getMaximumLineLength()
    {
        return 79;
    }

    /**
     * Outputs specified text to the console window
     * You can specify arguments that will be passed to the text via sprintf.
     *
     * @see http://www.php.net/sprintf
     *
     * @param string $text      Text to output
     * @param array  $arguments Optional arguments to use for sprintf
     *
     * @return void
     */
    public function output($text, array $arguments = [])
    {
        if ($arguments !== []) {
            $text = vsprintf($text, $arguments);
        }
        $this->output->write($text);
    }

    /**
     * Outputs specified text to the console window and appends a line break.
     *
     * @param string $text      Text to output
     * @param array  $arguments Optional arguments to use for sprintf
     *
     * @return void
     *
     * @see output()
     * @see outputLines()
     */
    public function outputLine($text = '', array $arguments = [])
    {
        $this->output($text.PHP_EOL, $arguments);
    }

    /**
     * Formats the given text to fit into the maximum line length and outputs it to the
     * console window.
     *
     * @param string $text        Text to output
     * @param array  $arguments   Optional arguments to use for sprintf
     * @param int    $leftPadding The number of spaces to use for indentation
     *
     * @return void
     *
     * @see outputLine()
     */
    public function outputFormatted($text = '', array $arguments = [], $leftPadding = 0)
    {
        $lines = explode(PHP_EOL, $text);
        foreach ($lines as $line) {
            $formattedText = str_repeat(' ', $leftPadding).wordwrap($line, $this->getMaximumLineLength() - $leftPadding, PHP_EOL.str_repeat(' ', $leftPadding), true);
            $this->outputLine($formattedText, $arguments);
        }
    }

    /**
     * Renders a table like output of the given $rows.
     *
     * @param array $rows
     * @param array $headers
     */
    public function outputTable($rows, $headers = null)
    {
        $tableHelper = $this->getTableHelper();
        if ($headers !== null) {
            $tableHelper->setHeaders($headers);
        }
        $tableHelper->setRows($rows);
        $tableHelper->render($this->output);
    }

    /**
     * Asks the user to select a value.
     *
     * @param string|array $question    The question to ask. If an array each array item is turned into one line of a multi-line question
     * @param array        $choices     List of choices to pick from
     * @param bool         $default     The default answer if the user enters nothing
     * @param bool         $multiSelect If TRUE the result will be an array with the selected options. Multiple options can be given separated by commas
     * @param bool|int     $attempts    Max number of times to ask before giving up (false by default, which means infinite)
     *
     * @throws \InvalidArgumentException
     *
     * @return int|string|array The selected value or values (the key of the choices array)
     */
    public function select($question, $choices, $default = null, $multiSelect = false, $attempts = false)
    {
        return $this->getDialogHelper()->select($this->output, $question, $choices, $default, $attempts, 'Value "%s" is invalid', $multiSelect);
    }

    /**
     * Asks a question to the user.
     *
     * @param string|array $question     The question to ask. If an array each array item is turned into one line of a multi-line question
     * @param string       $default      The default answer if none is given by the user
     * @param array        $autocomplete List of values to autocomplete. This only works if "stty" is installed
     *
     * @throws \RuntimeException If there is no data to read in the input stream
     *
     * @return string The user answer
     */
    public function ask($question, $default = null, array $autocomplete = null)
    {
        return $this->getDialogHelper()->ask($this->output, $question, $default, $autocomplete);
    }

    /**
     * Asks a confirmation to the user.
     *
     * The question will be asked until the user answers by nothing, yes, or no.
     *
     * @param string|array $question The question to ask. If an array each array item is turned into one line of a multi-line question
     * @param bool         $default  The default answer if the user enters nothing
     *
     * @return bool true if the user has confirmed, false otherwise
     */
    public function askConfirmation($question, $default = true)
    {
        return $this->getDialogHelper()->askConfirmation($this->output, $question, $default);
    }

    /**
     * Asks a question to the user, the response is hidden.
     *
     * @param string|array $question The question. If an array each array item is turned into one line of a multi-line question
     * @param bool         $fallback In case the response can not be hidden, whether to fallback on non-hidden question or not
     *
     * @throws \RuntimeException In case the fallback is deactivated and the response can not be hidden
     *
     * @return string The answer
     */
    public function askHiddenResponse($question, $fallback = true)
    {
        return $this->getDialogHelper()->askHiddenResponse($this->output, $question, $fallback);
    }

    /**
     * Asks for a value and validates the response.
     *
     * The validator receives the data to validate. It must return the
     * validated data when the data is valid and throw an exception
     * otherwise.
     *
     * @param string|array $question     The question to ask. If an array each array item is turned into one line of a multi-line question
     * @param callable     $validator    A PHP callback that gets a value and is expected to return the (transformed) value or throw an exception if it wasn't valid
     * @param int|bool     $attempts     Max number of times to ask before giving up (false by default, which means infinite)
     * @param string       $default      The default answer if none is given by the user
     * @param array        $autocomplete List of values to autocomplete. This only works if "stty" is installed
     *
     * @throws \Exception When any of the validators return an error
     *
     * @return mixed
     */
    public function askAndValidate($question, $validator, $attempts = false, $default = null, array $autocomplete = null)
    {
        return $this->getDialogHelper()->askAndValidate($this->output, $question, $validator, $attempts, $default, $autocomplete);
    }

    /**
     * Asks for a value, hide and validates the response.
     *
     * The validator receives the data to validate. It must return the
     * validated data when the data is valid and throw an exception
     * otherwise.
     *
     * @param string|array $question  The question to ask. If an array each array item is turned into one line of a multi-line question
     * @param callable     $validator A PHP callback that gets a value and is expected to return the (transformed) value or throw an exception if it wasn't valid
     * @param int|bool     $attempts  Max number of times to ask before giving up (false by default, which means infinite)
     * @param bool         $fallback  In case the response can not be hidden, whether to fallback on non-hidden question or not
     *
     * @throws \Exception        When any of the validators return an error
     * @throws \RuntimeException In case the fallback is deactivated and the response can not be hidden
     *
     * @return string The response
     */
    public function askHiddenResponseAndValidate($question, $validator, $attempts = false, $fallback = true)
    {
        return $this->getDialogHelper()->askHiddenResponseAndValidate($this->output, $question, $validator, $attempts, $fallback);
    }

    /**
     * Starts the progress output.
     *
     * @param int $max Maximum steps. If NULL an indeterminate progress bar is rendered
     *
     * @return void
     */
    public function progressStart($max = null)
    {
        $this->getProgressHelper()->start($this->output, $max);
    }

    /**
     * Advances the progress output X steps.
     *
     * @param int  $step   Number of steps to advance
     * @param bool $redraw Whether to redraw or not
     *
     * @throws \LogicException
     *
     * @return void
     */
    public function progressAdvance($step = 1, $redraw = false)
    {
        $this->getProgressHelper()->advance($step, $redraw);
    }

    /**
     * Sets the current progress.
     *
     * @param int  $current The current progress
     * @param bool $redraw  Whether to redraw or not
     *
     * @throws \LogicException
     *
     * @return void
     */
    public function progressSet($current, $redraw = false)
    {
        $this->getProgressHelper()->setCurrent($current, $redraw);
    }

    /**
     * Finishes the progress output.
     *
     * @return void
     */
    public function progressFinish()
    {
        $this->getProgressHelper()->finish();
    }

    /**
     * Returns or initializes the symfony/console DialogHelper.
     *
     * @return DialogHelper
     */
    protected function getDialogHelper()
    {
        if ($this->dialogHelper === null) {
            $this->dialogHelper = new DialogHelper();
            $helperSet = new HelperSet([new FormatterHelper()]);
            $this->dialogHelper->setHelperSet($helperSet);
        }

        return $this->dialogHelper;
    }

    /**
     * Returns or initializes the symfony/console ProgressHelper.
     *
     * @return ProgressHelper
     */
    protected function getProgressHelper()
    {
        if ($this->progressHelper === null) {
            $this->progressHelper = new ProgressHelper();
        }

        return $this->progressHelper;
    }

    /**
     * Returns or initializes the symfony/console TableHelper.
     *
     * @return TableHelper
     */
    protected function getTableHelper()
    {
        if ($this->tableHelper === null) {
            $this->tableHelper = new TableHelper();
        }

        return $this->tableHelper;
    }
}
