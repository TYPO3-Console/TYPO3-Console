<?php
namespace Helhum\Typo3Console\Mvc\Cli;

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
    protected $commandIdentifier;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\MethodReflection
     */
    protected $commandMethodReflection;

    /**
     * Name of the extension to which this command belongs
     *
     * @var string
     */
    protected $extensionName;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
     */
    public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Constructor
     *
     * @param string $controllerClassName Class name of the controller providing the command
     * @param string $controllerCommandName Command name, i.e. the method name of the command, without the "Command" suffix
     * @throws \InvalidArgumentException
     */
    public function __construct($controllerClassName, $controllerCommandName)
    {
        $this->controllerClassName = $controllerClassName;
        $this->controllerCommandName = $controllerCommandName;
        $delimiter = strpos($controllerClassName, '\\') !== false ? '\\' : '_';
        $classNameParts = explode($delimiter, $controllerClassName);
        if (isset($classNameParts[0]) && $classNameParts[0] === 'TYPO3' && isset($classNameParts[1]) && $classNameParts[1] === 'CMS') {
            $classNameParts[0] .= '\\' . $classNameParts[1];
            unset($classNameParts[1]);
            $classNameParts = array_values($classNameParts);
        }
        if (count($classNameParts) !== 4 || strpos($classNameParts[3], 'CommandController') === false) {
            throw new \InvalidArgumentException(
                'Invalid controller class name "' . $controllerClassName . '". Class name must have exactly 4 parts and must end with "CommandController" (e.g. Vendor\ExtensionName\Command\MySimpleCommandController).',
                1305100019
            );
        }
        $this->extensionName = $classNameParts[1];
        $extensionKey = \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($this->extensionName);
        $this->commandIdentifier = strtolower($extensionKey . ':' . substr($classNameParts[3], 0, -17) . ':' . $controllerCommandName);
    }

    /**
     * @return string
     */
    public function getControllerClassName()
    {
        return $this->controllerClassName;
    }

    /**
     * @return string
     */
    public function getControllerCommandName()
    {
        return $this->controllerCommandName;
    }

    /**
     * Returns the command identifier for this command
     *
     * @return string The command identifier for this command, following the pattern extensionname:controllername:commandname
     */
    public function getCommandIdentifier()
    {
        return $this->commandIdentifier;
    }

    /**
     * Returns the name of the extension to which this command belongs
     *
     * @return string
     */
    public function getExtensionName()
    {
        return $this->extensionName;
    }

    /**
     * Returns a short description of this command
     *
     * @return string A short description
     */
    public function getShortDescription()
    {
        $lines = explode(LF, $this->getCommandMethodReflection()->getDescription());
        return !empty($lines) ? trim($lines[0]) : '<no description available>';
    }

    /**
     * Returns a longer description of this command
     * This is the complete method description except for the first line which can be retrieved via getShortDescription()
     * If The command description only consists of one line, an empty string is returned
     *
     * @return string A longer description of this command
     */
    public function getDescription()
    {
        $lines = explode(LF, $this->getCommandMethodReflection()->getDescription());
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
     * Returns TRUE if this command expects required and/or optional arguments, otherwise FALSE
     *
     * @return bool
     */
    public function hasArguments()
    {
        return !empty($this->getCommandMethodReflection()->getParameters());
    }

    /**
     * Returns an array of \TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition that contains
     * information about required/optional arguments of this command.
     * If the command does not expect any arguments, an empty array is returned
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition[]
     */
    public function getArgumentDefinitions()
    {
        if (!$this->hasArguments()) {
            return [];
        }
        $commandArgumentDefinitions = [];
        $commandMethodReflection = $this->getCommandMethodReflection();
        $annotations = $commandMethodReflection->getTagsValues();
        $commandParameters = $this->reflectionService->getMethodParameters($this->controllerClassName, $this->controllerCommandName . 'Command');
        $i = 0;
        foreach ($commandParameters as $commandParameterName => $commandParameterDefinition) {
            $explodedAnnotation = preg_split('/\s+/', $annotations['param'][$i], 3);
            $description = !empty($explodedAnnotation[2]) ? $explodedAnnotation[2] : '';
            $required = $commandParameterDefinition['optional'] !== true;
            $commandArgumentDefinitions[] = new \TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition($commandParameterName, $required, $description);
            $i++;
        }
        return $commandArgumentDefinitions;
    }

    /**
     * Tells if this command is internal and thus should not be exposed through help texts, user documentation etc.
     * Internal commands are still accessible through the regular command line interface, but should not be used
     * by users.
     *
     * @return bool
     */
    public function isInternal()
    {
        return $this->getCommandMethodReflection()->isTaggedWith('internal');
    }

    /**
     * Tells if this command is meant to be used on CLI only.
     *
     * @return bool
     */
    public function isCliOnly()
    {
        return $this->getCommandMethodReflection()->isTaggedWith('cli');
    }

    /**
     * Tells if this command flushes all caches and thus needs special attention in the interactive shell.
     *
     * Note that neither this method nor the @flushesCaches annotation is currently part of the official API.
     *
     * @return bool
     */
    public function isFlushingCaches()
    {
        return $this->getCommandMethodReflection()->isTaggedWith('flushesCaches');
    }

    /**
     * Returns an array of command identifiers which were specified in the "@see"
     * annotation of a command method.
     *
     * @return array
     */
    public function getRelatedCommandIdentifiers()
    {
        $commandMethodReflection = $this->getCommandMethodReflection();
        if (!$commandMethodReflection->isTaggedWith('see')) {
            return [];
        }
        $relatedCommandIdentifiers = [];
        foreach ($commandMethodReflection->getTagValues('see') as $tagValue) {
            if (preg_match('/^[\\w\\d\\.]+:[\\w\\d]+:[\\w\\d]+$/', $tagValue) === 1) {
                $relatedCommandIdentifiers[] = $tagValue;
            }
        }
        return $relatedCommandIdentifiers;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Reflection\MethodReflection
     */
    protected function getCommandMethodReflection()
    {
        if ($this->commandMethodReflection === null) {
            $this->commandMethodReflection = new \TYPO3\CMS\Extbase\Reflection\MethodReflection($this->controllerClassName, $this->controllerCommandName . 'Command');
        }
        return $this->commandMethodReflection;
    }
}
