<?php
declare(strict_types=1);
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

use Doctrine\Common\Annotations\AnnotationReader;
use Helhum\Typo3Console\Annotation\Command\Definition\Argument;
use Helhum\Typo3Console\Annotation\Command\Definition\Option;
use Helhum\Typo3Console\Annotation\Command\Definition\Validate;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Represents a Command
 */
class Command
{
    /**
     * @var string
     */
    protected $controllerClassName;

    /**
     * @var string
     */
    protected $controllerCommandName;

    /**
     * @var string
     */
    private $controllerCommandMethod;

    /**
     * @var string
     */
    protected $commandIdentifier;

    /**
     * Name of the extension to which this command belongs
     *
     * @var string
     */
    protected $extensionName;

    /**
     * @var CommandArgumentDefinition[]
     */
    private $argumentDefinitions;

    /**
     * @var CommandArgumentDefinition[]
     */
    private $arguments = [];

    /**
     * @var CommandArgumentDefinition[]
     */
    private $options = [];

    /**
     * @var string[]
     */
    private $synopsis;

    /**
     * @var array
     */
    private $commandMethodDefinitions = null;

    /**
     * @var array
     */
    private $inputDefinitions;

    /**
     * @var CommandReflection
     */
    private $commandReflection;

    /**
     * @param string $controllerClassName Class name of the controller providing the command
     * @param string $controllerCommandName Command name, i.e. the method name of the command, without the "Command" suffix
     * @param CommandReflection $commandReflection
     * @throws InvalidArgumentException
     */
    public function __construct(string $controllerClassName, string $controllerCommandName, CommandReflection $commandReflection = null)
    {
        $this->controllerClassName = $controllerClassName;
        $this->controllerCommandName = $controllerCommandName;
        $this->controllerCommandMethod = $this->controllerCommandName . 'Command';
        $this->commandReflection = $commandReflection ?: new CommandReflection($this->controllerClassName, $this->controllerCommandMethod);
        $delimiter = strpos($controllerClassName, '\\') !== false ? '\\' : '_';
        $classNameParts = explode($delimiter, $controllerClassName);
        if (isset($classNameParts[0], $classNameParts[1]) && $classNameParts[0] === 'TYPO3' && $classNameParts[1] === 'CMS') {
            $classNameParts[0] .= '\\' . $classNameParts[1];
            unset($classNameParts[1]);
            $classNameParts = array_values($classNameParts);
        }
        $numberOfClassNameParts = count($classNameParts);
        if ($numberOfClassNameParts < 3) {
            throw new InvalidArgumentException(
                'Controller class names must at least consist of three parts: vendor, extension name and command controller name.',
                1438782187
            );
        }
        if (strpos($classNameParts[$numberOfClassNameParts - 1], 'CommandController') === false) {
            throw new InvalidArgumentException(
                'Invalid controller class name "' . $controllerClassName . '". Class name must end with "CommandController" (e.g. Vendor\ExtensionName\Command\MySimpleCommandController).',
                1305100019
            );
        }
        $this->extensionName = $classNameParts[1];
        $extensionKey = GeneralUtility::camelCaseToLowerCaseUnderscored($this->extensionName);
        $this->commandIdentifier = strtolower($extensionKey . ':' . substr($classNameParts[$numberOfClassNameParts - 1], 0, -17) . ':' . $controllerCommandName);
    }

    /**
     * @return string
     */
    public function getControllerClassName(): string
    {
        return $this->controllerClassName;
    }

    /**
     * @return string
     */
    public function getControllerCommandName(): string
    {
        return $this->controllerCommandName;
    }

    /**
     * Returns the command identifier for this command
     *
     * @return string The command identifier for this command, following the pattern extensionname:controllername:commandname
     */
    public function getCommandIdentifier(): string
    {
        return $this->commandIdentifier;
    }

    /**
     * Returns the name of the extension to which this command belongs
     *
     * @return string
     */
    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    /**
     * Returns a short description of this command
     *
     * @return string A short description
     */
    public function getShortDescription(): string
    {
        $lines = explode(LF, $this->commandReflection->getDescription());

        return !empty($lines) ? trim($lines[0]) : '<no description available>';
    }

    /**
     * Returns a longer description of this command
     * This is the complete method description except for the first line which can be retrieved via getShortDescription()
     * If The command description only consists of one line, an empty string is returned
     *
     * @return string A longer description of this command
     */
    public function getDescription(): string
    {
        $lines = explode(LF, $this->commandReflection->getDescription());
        array_shift($lines);
        $descriptionLines = [];
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($descriptionLines !== [] || $trimmedLine !== '') {
                $descriptionLines[] = $trimmedLine;
            }
        }

        return implode(LF, $descriptionLines);
    }

    /**
     * Returns true if this command expects required and/or optional arguments, otherwise false
     *
     * @return bool
     */
    public function hasArguments(): bool
    {
        return !empty($this->commandReflection->getParameters());
    }

    /**
     * @return bool
     */
    public function hasOptions(): bool
    {
        return count($this->getOptions()) > 0;
    }

    /**
     * @return bool
     */
    public function hasRequiredArguments(): bool
    {
        return count($this->getArguments()) > 0;
    }

    /**
     * @return CommandArgumentDefinition[]
     */
    public function getArguments(): array
    {
        if ($this->argumentDefinitions === null) {
            $this->getArgumentDefinitions();
        }

        return $this->arguments;
    }

    /**
     * @return CommandArgumentDefinition[]
     */
    public function getOptions(): array
    {
        if ($this->argumentDefinitions === null) {
            $this->getArgumentDefinitions();
        }

        return $this->options;
    }

    /**
     * @return bool
     * @deprecated in 5.0 will be removed in 6.0
     */
    public function shouldValidateInputStrict(): bool
    {
        return !empty($this->getCommandMethodDefinitions()['Validate']->strict);
    }

    /**
     * Returns an array of \TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition that contains
     * information about required/optional arguments of this command.
     * If the command does not expect any arguments, an empty array is returned
     *
     * @return CommandArgumentDefinition[]
     */
    public function getArgumentDefinitions(): array
    {
        if ($this->argumentDefinitions !== null) {
            return $this->argumentDefinitions;
        }
        if (!$this->hasArguments()) {
            return $this->argumentDefinitions = [];
        }
        $this->argumentDefinitions = [];
        $commandParameters = $this->commandReflection->getParameters();
        $commandParameterTags = $this->commandReflection->getTagsValues()['param'];
        $i = 0;
        $definedArguments = $this->getDefinedArguments();
        $definedOptions = $this->getDefinedOptions();
        foreach ($commandParameters as $commandParameterName => $commandParameterDefinition) {
            $description = '';
            if (isset($commandParameterTags[$i])) {
                $explodedAnnotation = preg_split('/\s+/', $commandParameterTags[$i], 3);
                $description = !empty($explodedAnnotation[2]) ? $explodedAnnotation[2] : '';
            }
            $dataType = $commandParameterDefinition['type'] ?? 'null';
            if ($commandParameterDefinition['array']) {
                $dataType = 'array';
            }
            $default = $commandParameterDefinition['defaultValue'] ?? null;
            $required = $commandParameterDefinition['optional'] !== true;
            $isArgument = isset($definedArguments[$commandParameterName]) || ($required && !isset($definedOptions[$commandParameterName]));
            $argumentDefinition = new CommandArgumentDefinition($commandParameterName, $required, $description, $dataType, $default, $isArgument);
            if ($isArgument) {
                $this->arguments[] = $argumentDefinition;
            } else {
                $this->options[] = $argumentDefinition;
            }
            $this->argumentDefinitions[] = $argumentDefinition;
            $i++;
        }

        return $this->argumentDefinitions;
    }

    public function getInputDefinitions(bool $strict = null): array
    {
        if ($strict === null) {
            $strict = $this->shouldValidateInputStrict();
        }
        $key = $strict ? 'strict' : 'relaxed';

        if (!empty($this->inputDefinitions[$key])) {
            return $this->inputDefinitions[$key];
        }

        $applicationDefinition = (new Application())->getDefinition();
        $definitions = [];
        foreach ($this->getArguments() as $argument) {
            $argumentName = $argument->getName();
            if (!$strict && $applicationDefinition->hasArgument($argumentName)) {
                // That is most likely the "command" argument
                // We append the full command identifier so we don't get an exception from Symfony
                $argumentName .= '_' . $this->getCommandIdentifier();
            }
            $definitions[] = new InputArgument(
                $argumentName,
                $strict && $argument->isRequired() ? InputArgument::REQUIRED : InputArgument::OPTIONAL,
                $argument->getDescription(),
                $strict && $argument->isRequired() ? null : $argument->getDefaultValue()
            );

            if ($strict) {
                continue;
            }
            // Fallback option for compatibility but only in case no other option with same name is defined already
            // @deprecated in 5.0 will be removed in 6.0
            if ($applicationDefinition->hasOption($argument->getOptionName())) {
                // Option is globally defined already, so we can skip here
                continue;
            }
            $definitions[] = new InputOption(
                $argument->getOptionName(),
                null,
                InputOption::VALUE_REQUIRED,
                $argument->getDescription()
            );
        }
        foreach ($this->getOptions() as $option) {
            if ($applicationDefinition->hasOption($option->getOptionName())) {
                // Option is globally defined already, so we can skip here
                continue;
            }
            if (!$option->acceptsValue()) {
                $definitions[] = new InputOption(
                    $option->getOptionName(),
                    null,
                    $strict ? InputOption::VALUE_NONE : InputOption::VALUE_OPTIONAL,
                    $option->getDescription()
                );
            } else {
                $definitions[] = new InputOption(
                    $option->getOptionName(),
                    null,
                    InputOption::VALUE_REQUIRED,
                    $option->getDescription(),
                    $option->getDefaultValue()
                );
            }

            if ($strict) {
                continue;
            }
            // Fallback to argument for compatibility
            // @deprecated in 5.0 will be removed in 6.0
            $definitions[] = new InputArgument(
                $option->getName(),
                InputArgument::OPTIONAL,
                $option->getDescription(),
                $option->getDefaultValue()
            );
        }

        return $this->inputDefinitions[$key] = $definitions;
    }

    private function getDefinedArguments(): array
    {
        $argumentDefinitions = $this->getCommandMethodDefinitions()['Argument'] ?? [];
        if (empty($argumentDefinitions)) {
            return [];
        }
        $definedArguments = [];
        foreach ($argumentDefinitions as $definedArgument) {
            $definedArguments[$definedArgument->name] = $definedArgument;
        }

        return $definedArguments;
    }

    private function getDefinedOptions(): array
    {
        $optionDefinitions = $this->getCommandMethodDefinitions()['Option'] ?? [];
        if (empty($optionDefinitions)) {
            return [];
        }
        $definedOptions = [];
        foreach ($optionDefinitions as $definedOption) {
            $definedOptions[$definedOption->name] = $definedOption;
        }

        return $definedOptions;
    }

    private function getCommandMethodDefinitions(): array
    {
        if ($this->commandMethodDefinitions !== null) {
            return $this->commandMethodDefinitions;
        }
        $this->commandMethodDefinitions = $this->parseDefinitions();

        return $this->commandMethodDefinitions;
    }

    /**
     * Get parsed annotations if command has any
     *
     * @throws InvalidArgumentException
     * @return array
     */
    private function parseDefinitions(): array
    {
        $definitions = [];
        $reader = new AnnotationReader();
        $method = new \ReflectionMethod($this->controllerClassName, $this->controllerCommandMethod);
        foreach ($reader->getMethodAnnotations($method) as $annotation) {
            if ($annotation instanceof Option) {
                $definitions['Option'][] = $annotation;
            }
            if ($annotation instanceof Argument) {
                $definitions['Argument'][] = $annotation;
            }
            if ($annotation instanceof Validate) {
                $definitions['Validate'] = $annotation;
            }
        }

        return $definitions;
    }

    /**
     * Get synopsis for this command, either short or long
     *
     * @param bool $short
     * @return string
     */
    public function getSynopsis($short = false): string
    {
        $key = $short ? 'short' : 'long';
        if (isset($this->synopsis[$key])) {
            return $this->synopsis[$key];
        }

        $elements = [];
        if ($short && $this->hasOptions()) {
            $elements[] = '[options]';
        } elseif (!$short) {
            foreach ($this->getOptions() as $argumentDefinition) {
                $value = '';
                if ($argumentDefinition->acceptsValue()) {
                    $value = ' ' . strtoupper($argumentDefinition->getOptionName());
                }
                $elements[] = sprintf('[%s%s]', $argumentDefinition->getDashedName(), $value);
            }
        }
        if (count($elements) && $this->hasRequiredArguments()) {
            $elements[] = '[--]';
        }
        foreach ($this->getArguments() as $argumentDefinition) {
            $elements[] = '<' . $argumentDefinition->getName() . '>';
        }

        return $this->synopsis[$key] = implode(' ', $elements);
    }

    /**
     * Tells if this command is internal and thus should not be exposed through help texts, user documentation etc.
     * Internal commands are still accessible through the regular command line interface, but should not be used
     * by users.
     *
     * @return bool
     */
    public function isInternal(): bool
    {
        return isset($this->commandReflection->getTagsValues()['internal']);
    }

    /**
     * Tells if this command is meant to be used on CLI only.
     *
     * @return bool
     */
    public function isCliOnly(): bool
    {
        return isset($this->commandReflection->getTagsValues()['cli']);
    }

    /**
     * Tells if this command flushes all caches and thus needs special attention in the interactive shell.
     *
     * Note that neither this method nor the @flushesCaches annotation is currently part of the official API.
     *
     * @return bool
     */
    public function isFlushingCaches(): bool
    {
        return isset($this->commandReflection->getTagsValues()['flushesCaches']);
    }

    /**
     * Returns an array of command identifiers which were specified in the "@see"
     * annotation of a command method.
     *
     * @throws \ReflectionException
     * @return array
     */
    public function getRelatedCommandIdentifiers(): array
    {
        if (!isset($this->commandReflection->getTagsValues()['see'])) {
            return [];
        }
        $relatedCommandIdentifiers = [];
        foreach ($this->commandReflection->getTagsValues()['see'] as $tagValue) {
            if (preg_match('/^[\\w\\._]+:[\\w]+:[\\w]+$/', $tagValue) === 1) {
                $relatedCommandIdentifiers[] = $tagValue;
            }
        }

        return $relatedCommandIdentifiers;
    }
}
