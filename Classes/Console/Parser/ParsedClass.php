<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Parser;

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

/**
 * Class ParsedClass
 */
class ParsedClass
{
    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $namespaceSeparator = '\\';

    /**
     * @var bool
     */
    protected $interface = false;

    /**
     * @var bool
     */
    protected $abstract = false;

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param mixed $namespaceSeparator
     */
    public function setNamespaceSeparator($namespaceSeparator)
    {
        $this->namespaceSeparator = $namespaceSeparator;
    }

    /**
     * @return mixed
     */
    public function getNamespaceSeparator()
    {
        return $this->namespaceSeparator;
    }

    /**
     * @param bool $interface
     */
    public function setInterface($interface)
    {
        $this->interface = $interface;
    }

    /**
     * @return bool
     */
    public function isInterface()
    {
        return $this->interface;
    }

    /**
     * @param bool $abstract
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;
    }

    /**
     * @return bool
     */
    public function isAbstract()
    {
        return $this->abstract;
    }

    /**
     * @return string
     */
    public function getFullyQualifiedClassName()
    {
        return $this->namespace . $this->namespaceSeparator . $this->className;
    }
}
