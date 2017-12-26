<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\TYPO3v87\Mvc\Cli;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Reflection\MethodReflection;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

/**
 * Analyze command method arguments and tags with PHP reflection
 */
class CommandReflection
{
    /**
     * @var string
     */
    private $controllerClassName;

    /**
     * @var string
     */
    private $controllerCommandMethod;

    /**
     * @var MethodReflection
     */
    private $commandMethodReflection;

    /**
     * @var ReflectionService
     */
    private $reflectionService;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    public function __construct(string $controllerClassName, string $controllerCommandMethod, ReflectionService $reflectionService = null, AnnotationReader $annotationReader = null)
    {
        $this->controllerClassName = $controllerClassName;
        $this->controllerCommandMethod = $controllerCommandMethod;
        $this->reflectionService = $reflectionService ?: GeneralUtility::makeInstance(ObjectManager::class)->get(ReflectionService::class);
        $this->commandMethodReflection = new MethodReflection($this->controllerClassName, $this->controllerCommandMethod);
        $this->annotationReader = $annotationReader ?: new AnnotationReader();
    }

    public function getDescription(): string
    {
        return $this->commandMethodReflection->getDescription();
    }

    public function getParameters(): array
    {
        return $this->reflectionService->getMethodParameters($this->controllerClassName, $this->controllerCommandMethod);
    }

    public function getTagsValues(): array
    {
        return $this->commandMethodReflection->getTagsValues();
    }
}
