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

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Represents a CommandArgumentDefinition
 */
class CommandArgumentDefinition
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $required;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $dataType;

    /**
     * @var mixed
     */
    private $defaultValue;

    /**
     * @var null
     */
    private $isArgument;

    /**
     * Constructor
     *
     * @param string $name name of the command argument (= parameter name)
     * @param bool $required defines whether this argument is required or optional
     * @param string $description description of the argument
     * @param string $dataType data type (boolean, string or array)
     * @param mixed $defaultValue
     * @param bool|null $isArgument
     */
    public function __construct(string $name, bool $required, string $description, string $dataType, $defaultValue = null, bool $isArgument = null)
    {
        $this->name = $name;
        $this->required = $required;
        $this->description = $description;
        $this->dataType = $dataType;
        $this->defaultValue = $defaultValue;
        $this->isArgument = $isArgument;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the lower cased name with dashes as word separator
     *
     * @return string
     */
    public function getDashedName(): string
    {
        return '--' . $this->getOptionName();
    }

    /**
     * @return string
     */
    public function getOptionName(): string
    {
        $dashedName = ucfirst($this->name);
        $dashedName = preg_replace('/([A-Z][a-z0-9]*)/', '$1-', $dashedName);

        return strtolower(substr($dashedName, 0, -1));
    }

    /**
     * @return null
     */
    public function isArgument()
    {
        return $this->required || $this->isArgument === true;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return string
     */
    public function getDataType(): string
    {
        return $this->dataType;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @return bool
     */
    public function acceptsValue(): bool
    {
        return $this->dataType !== 'boolean';
    }
}
