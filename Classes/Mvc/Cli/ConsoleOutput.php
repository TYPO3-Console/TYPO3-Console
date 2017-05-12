<?php
namespace Helhum\Typo3Console\Mvc\Cli;

/*
 * This file is part of the TYPO3 Console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * A wrapper for Symfony ConsoleOutput and related helpers
 */
class ConsoleOutput
{
    /**
     * @var ArgvInput
     */
    protected $input;

    /**
     * @var SymfonyConsoleOutput
     */
    protected $output;

    /**
     * @var QuestionHelper
     */
    protected $questionHelper;

    /**
     * @var ProgressBar
     */
    protected $progressBar;

    /**
     * @var Table
     */
    protected $table;

    /**
     * Creates and initializes the Symfony I/O instances
     *
     * @param Output|null $output
     * @param Input|null $input
     */
    public function __construct(Output $output = null, Input $input = null)
    {
        $this->output = $output ?: new SymfonyConsoleOutput();
        $this->output->getFormatter()->setStyle('b', new OutputFormatterStyle(null, null, ['bold']));
        $this->output->getFormatter()->setStyle('i', new OutputFormatterStyle('black', 'white'));
        $this->output->getFormatter()->setStyle('u', new OutputFormatterStyle(null, null, ['underscore']));
        $this->output->getFormatter()->setStyle('em', new OutputFormatterStyle(null, null, ['reverse']));
        $this->output->getFormatter()->setStyle('strike', new OutputFormatterStyle(null, null, ['conceal']));
        $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('green'));
        $this->output->getFormatter()->setStyle('warning', new OutputFormatterStyle('black', 'yellow'));
        $this->output->getFormatter()->setStyle('ins', new OutputFormatterStyle('green'));
        $this->output->getFormatter()->setStyle('del', new OutputFormatterStyle('red'));
        $this->output->getFormatter()->setStyle('code', new OutputFormatterStyle(null, null, ['bold']));
        $this->input = $input;
    }

    /**
     * @return SymfonyConsoleOutput
     */
    public function getSymfonyConsoleOutput()
    {
        return $this->output;
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
     * You can specify arguments that will be passed to the text via sprintf
     * @see http://www.php.net/sprintf
     *
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     * @return void
     */
    public function output($text, array $arguments = [])
    {
        if ($arguments !== []) {
            $text = vsprintf($text, $arguments);
        }
        if (getenv('TYPO3_CONSOLE_SUB_PROCESS')) {
            $this->output->write($text, false, OutputInterface::OUTPUT_RAW);
        } else {
            $this->output->write($text);
        }
    }

    /**
     * Outputs specified text to the console window and appends a line break
     *
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     * @return void
     * @see output()
     * @see outputLines()
     */
    public function outputLine($text = '', array $arguments = [])
    {
        $this->output($text . PHP_EOL, $arguments);
    }

    /**
     * Formats the given text to fit into the maximum line length and outputs it to the
     * console window
     *
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     * @param int $leftPadding The number of spaces to use for indentation
     * @return void
     * @see outputLine()
     */
    public function outputFormatted($text = '', array $arguments = [], $leftPadding = 0)
    {
        $lines = explode(PHP_EOL, $text);
        foreach ($lines as $line) {
            $formattedText = str_repeat(' ', $leftPadding) . wordwrap($line, $this->getMaximumLineLength() - $leftPadding, PHP_EOL . str_repeat(' ', $leftPadding), true);
            $this->outputLine($formattedText, $arguments);
        }
    }

    /**
     * Renders a table like output of the given $rows
     *
     * @param array $rows
     * @param array $headers
     */
    public function outputTable($rows, $headers = null)
    {
        $table = $this->getTable();
        if ($headers !== null) {
            $table->setHeaders($headers);
        }
        $table->setRows($rows);
        $table->render();
    }

    /**
     * Asks the user to select a value
     *
     * @param string|array $question The question to ask. If an array each array item is turned into one line of a multi-line question
     * @param array $choices List of choices to pick from
     * @param bool $default The default answer if the user enters nothing
     * @param bool $multiSelect If true the result will be an array with the selected options. Multiple options can be given separated by commas
     * @param bool|int $attempts Max number of times to ask before giving up (false by default, which means infinite)
     * @throws \InvalidArgumentException
     * @return int|string|array The selected value or values (the key of the choices array)
     */
    public function select($question, $choices, $default = null, $multiSelect = false, $attempts = false)
    {
        $question = (new ChoiceQuestion($question, $choices, $default))
            ->setMultiselect($multiSelect)
            ->setMaxAttempts($attempts)
            ->setErrorMessage('Value "%s" is invalid');

        return $this->getQuestionHelper()->ask($this->getInput(), $this->output, $question);
    }

    /**
     * Asks a question to the user
     *
     * @param string|array $question The question to ask. If an array each array item is turned into one line of a multi-line question
     * @param string $default The default answer if none is given by the user
     * @param array $autocomplete List of values to autocomplete. This only works if "stty" is installed
     * @throws \RuntimeException If there is no data to read in the input stream
     * @return string The user answer
     */
    public function ask($question, $default = null, array $autocomplete = null)
    {
        $question = (new Question($question, $default))
            ->setAutocompleterValues($autocomplete);

        return $this->getQuestionHelper()->ask($this->getInput(), $this->output, $question);
    }

    /**
     * Asks a confirmation to the user.
     *
     * The question will be asked until the user answers by nothing, yes, or no.
     *
     * @param string|array $question The question to ask. If an array each array item is turned into one line of a multi-line question
     * @param bool $default The default answer if the user enters nothing
     * @return bool true if the user has confirmed, false otherwise
     */
    public function askConfirmation($question, $default = true)
    {
        $question = new ConfirmationQuestion($question, $default);

        return $this->getQuestionHelper()->ask($this->getInput(), $this->output, $question);
    }

    /**
     * Asks a question to the user, the response is hidden
     *
     * @param string|array $question The question. If an array each array item is turned into one line of a multi-line question
     * @param bool $fallback In case the response can not be hidden, whether to fallback on non-hidden question or not
     * @throws \RuntimeException In case the fallback is deactivated and the response can not be hidden
     * @return string The answer
     */
    public function askHiddenResponse($question, $fallback = true)
    {
        $question = (new Question($question))
            ->setHidden(true)
            ->setHiddenFallback($fallback);

        return $this->getQuestionHelper()->ask($this->getInput(), $this->output, $question);
    }

    /**
     * Asks for a value and validates the response
     *
     * The validator receives the data to validate. It must return the
     * validated data when the data is valid and throw an exception
     * otherwise.
     *
     * @param string|array $question The question to ask. If an array each array item is turned into one line of a multi-line question
     * @param callable $validator A PHP callback that gets a value and is expected to return the (transformed) value or throw an exception if it wasn't valid
     * @param int|bool $attempts Max number of times to ask before giving up (false by default, which means infinite)
     * @param string $default The default answer if none is given by the user
     * @param array $autocomplete List of values to autocomplete. This only works if "stty" is installed
     * @throws \Exception When any of the validators return an error
     * @return mixed
     */
    public function askAndValidate($question, $validator, $attempts = false, $default = null, array $autocomplete = null)
    {
        $question = (new Question($question, $default))
            ->setValidator($validator)
            ->setMaxAttempts($attempts)
            ->setAutocompleterValues($autocomplete);

        return $this->getQuestionHelper()->ask($this->getInput(), $this->output, $question);
    }

    /**
     * Asks for a value, hide and validates the response
     *
     * The validator receives the data to validate. It must return the
     * validated data when the data is valid and throw an exception
     * otherwise.
     *
     * @param string|array $question The question to ask. If an array each array item is turned into one line of a multi-line question
     * @param callable $validator A PHP callback that gets a value and is expected to return the (transformed) value or throw an exception if it wasn't valid
     * @param int|bool $attempts Max number of times to ask before giving up (false by default, which means infinite)
     * @param bool $fallback In case the response can not be hidden, whether to fallback on non-hidden question or not
     * @throws \Exception When any of the validators return an error
     * @throws \RuntimeException In case the fallback is deactivated and the response can not be hidden
     * @return string The response
     */
    public function askHiddenResponseAndValidate($question, $validator, $attempts = false, $fallback = true)
    {
        $question = (new Question($question))
            ->setValidator($validator)
            ->setMaxAttempts($attempts)
            ->setHidden(true)
            ->setHiddenFallback($fallback);

        return $this->getQuestionHelper()->ask($this->getInput(), $this->output, $question);
    }

    /**
     * Starts the progress output
     *
     * @param int $max Maximum steps. If NULL an indeterminate progress bar is rendered
     * @return void
     */
    public function progressStart($max = null)
    {
        $this->getProgressBar()->start($max);
    }

    /**
     * Advances the progress output X steps
     *
     * @param int $step Number of steps to advance
     * @throws \LogicException
     * @return void
     */
    public function progressAdvance($step = 1)
    {
        $this->getProgressBar()->advance($step);
    }

    /**
     * Sets the current progress
     *
     * @param int $current The current progress
     * @throws \LogicException
     * @return void
     */
    public function progressSet($current)
    {
        $this->getProgressBar()->setProgress($current);
    }

    /**
     * Finishes the progress output
     *
     * @return void
     */
    public function progressFinish()
    {
        $this->getProgressBar()->finish();
    }

    /**
     * @throws \RuntimeException
     * @return ArgvInput
     */
    protected function getInput()
    {
        if ($this->input === null) {
            if (!isset($_SERVER['argv'])) {
                throw new \RuntimeException('Cannot initialize ArgvInput object without CLI context.', 1456914444);
            }
            $this->input = new ArgvInput();
        }

        return $this->input;
    }

    /**
     * Returns or initializes the symfony/console QuestionHelper
     *
     * @return QuestionHelper
     */
    protected function getQuestionHelper()
    {
        if ($this->questionHelper === null) {
            $this->questionHelper = new QuestionHelper();
            $helperSet = new HelperSet([new FormatterHelper()]);
            $this->questionHelper->setHelperSet($helperSet);
        }
        return $this->questionHelper;
    }

    /**
     * Returns or initializes the symfony/console ProgressBar
     *
     * @return ProgressBar
     */
    protected function getProgressBar()
    {
        if ($this->progressBar === null) {
            $this->progressBar = new ProgressBar($this->output);
        }
        return $this->progressBar;
    }

    /**
     * Returns or initializes the symfony/console Table
     *
     * @return Table
     */
    protected function getTable()
    {
        if ($this->table === null) {
            $this->table = new Table($this->output);
        }
        return $this->table;
    }
}
