<?php

namespace Helhum\Typo3Console\Mvc\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
use TYPO3\CMS\Extbase\Utility\TypeHandlingUtility;

/**
 * A controller argument.
 *
 * @api
 */
class Argument
{
    /**
     * @var \TYPO3\CMS\Extbase\Property\PropertyMapper
     * @inject
     */
    protected $propertyMapper;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfiguration
     * @inject
     */
    protected $propertyMappingConfiguration;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * Name of this argument.
     *
     * @var string
     */
    protected $name = '';

    /**
     * Short name of this argument.
     *
     * @var string
     */
    protected $shortName = null;

    /**
     * Data type of this argument's value.
     *
     * @var string
     */
    protected $dataType = null;

    /**
     * If the data type is an object, the class schema of the data type class is resolved.
     *
     * @var \TYPO3\CMS\Extbase\Reflection\ClassSchema
     */
    protected $dataTypeClassSchema;

    /**
     * TRUE if this argument is required.
     *
     * @var bool
     */
    protected $isRequired = false;

    /**
     * Actual value of this argument.
     *
     * @var object
     */
    protected $value = null;

    /**
     * Default value. Used if argument is optional.
     *
     * @var mixed
     */
    protected $defaultValue = null;

    /**
     * A custom validator, used supplementary to the base validation.
     *
     * @var \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface
     */
    protected $validator = null;

    /**
     * The validation results. This can be asked if the argument has errors.
     *
     * @var \TYPO3\CMS\Extbase\Error\Result
     */
    protected $validationResults = null;

    /**
     * Uid for the argument, if it has one.
     *
     * @var string
     */
    protected $uid = null;

    const ORIGIN_CLIENT = 0;
    const ORIGIN_PERSISTENCE = 1;
    const ORIGIN_PERSISTENCE_AND_MODIFIED = 2;
    const ORIGIN_NEWLY_CREATED = 3;

    /**
     * The origin of the argument value. This is only meaningful after argument mapping.
     *
     * One of the ORIGIN_* constants above
     *
     * @var int
     */
    protected $origin = 0;

    /**
     * Constructs this controller argument.
     *
     * @param string $name     Name of this argument
     * @param string $dataType The data type of this argument
     *
     * @throws \InvalidArgumentException if $name is not a string or empty
     *
     * @api
     */
    public function __construct($name, $dataType)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('$name must be of type string, '.gettype($name).' given.', 1187951688);
        }
        if (strlen($name) === 0) {
            throw new \InvalidArgumentException('$name must be a non-empty string, '.strlen($name).' characters given.', 1232551853);
        }
        $this->name = $name;
        $this->dataType = TypeHandlingUtility::normalizeType($dataType);
    }

    /**
     * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
     *
     * @return void
     */
    public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
        // Check for classnames (which have at least one underscore or backslash)
        $this->dataTypeClassSchema = strpbrk($this->dataType, '_\\') !== false ? $this->reflectionService->getClassSchema($this->dataType) : null;
    }

    /**
     * Returns the name of this argument.
     *
     * @return string This argument's name
     *
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the short name of this argument.
     *
     * @param string $shortName A "short name" - a single character
     *
     * @throws \InvalidArgumentException if $shortName is not a character
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\Argument $this
     *
     * @api
     */
    public function setShortName($shortName)
    {
        if ($shortName !== null && (!is_string($shortName) || strlen($shortName) !== 1)) {
            throw new \InvalidArgumentException('$shortName must be a single character or NULL', 1195824959);
        }
        $this->shortName = $shortName;

        return $this;
    }

    /**
     * Returns the short name of this argument.
     *
     * @return string This argument's short name
     *
     * @api
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * Sets the data type of this argument's value.
     *
     * @param string $dataType The data type. Can be either a built-in type such as "Text" or "Integer" or a fully qualified object name
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\Argument $this
     *
     * @api
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
        $this->dataTypeClassSchema = $this->reflectionService->getClassSchema($dataType);

        return $this;
    }

    /**
     * Returns the data type of this argument's value.
     *
     * @return string The data type
     *
     * @api
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * Marks this argument to be required.
     *
     * @param bool $required TRUE if this argument should be required
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\Argument $this
     *
     * @api
     */
    public function setRequired($required)
    {
        $this->isRequired = (bool) $required;

        return $this;
    }

    /**
     * Returns TRUE if this argument is required.
     *
     * @return bool TRUE if this argument is required
     *
     * @api
     */
    public function isRequired()
    {
        return $this->isRequired;
    }

    /**
     * Sets the default value of the argument.
     *
     * @param mixed $defaultValue Default value
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\Argument $this
     *
     * @api
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * Returns the default value of this argument.
     *
     * @return mixed The default value
     *
     * @api
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Sets a custom validator which is used supplementary to the base validation.
     *
     * @param \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface $validator The actual validator object
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\Argument Returns $this (used for fluent interface)
     *
     * @api
     */
    public function setValidator(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface $validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Returns the set validator.
     *
     * @return \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface The set validator, NULL if none was set
     *
     * @api
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Sets the value of this argument.
     *
     * @param mixed $rawValue The value of this argument
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentValueException if the argument is not a valid object of type $dataType
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\Argument
     */
    public function setValue($rawValue)
    {
        if ($rawValue === null) {
            $this->value = null;

            return $this;
        }
        if (is_object($rawValue) && $rawValue instanceof $this->dataType) {
            $this->value = $rawValue;

            return $this;
        }
        $this->value = $this->propertyMapper->convert($rawValue, $this->dataType, $this->propertyMappingConfiguration);
        $this->validationResults = $this->propertyMapper->getMessages();
        if ($this->validator !== null) {
            // TODO: Validation API has also changed!!!
            $validationMessages = $this->validator->validate($this->value);
            $this->validationResults->merge($validationMessages);
        }

        return $this;
    }

    /**
     * Returns the value of this argument.
     *
     * @return object The value of this argument - if none was set, NULL is returned
     *
     * @api
     */
    public function getValue()
    {
        if ($this->value === null) {
            return $this->defaultValue;
        } else {
            return $this->value;
        }
    }

    /**
     * Return the Property Mapping Configuration used for this argument; can be used by the initialize*action to modify the Property Mapping.
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfiguration
     *
     * @api
     */
    public function getPropertyMappingConfiguration()
    {
        return $this->propertyMappingConfiguration;
    }

    /**
     * @return bool TRUE if the argument is valid, FALSE otherwise
     *
     * @api
     */
    public function isValid()
    {
        return !$this->validationResults->hasErrors();
    }

    /**
     * @return array<\TYPO3\CMS\Extbase\Error\Result> Validation errors which have occurred.
     *
     * @api
     */
    public function getValidationResults()
    {
        return $this->validationResults;
    }

    /**
     * Returns a string representation of this argument's value.
     *
     * @return string
     *
     * @api
     */
    public function __toString()
    {
        return (string) $this->value;
    }
}
