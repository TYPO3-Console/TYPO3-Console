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

class PhpParser implements PhpParserInterface
{
    /**
     * @param string $classFile Path to PHP class file
     * @throws ParsingException
     * @return ParsedClass
     */
    public function parseClassFile($classFile): ParsedClass
    {
        if (!file_exists($classFile)) {
            throw new ParsingException('Class File does not exist', 1399284080);
        }
        try {
            return $this->parseClass(file_get_contents($classFile));
        } catch (ParsingException $e) {
            throw new ParsingException($e->getMessage() . ' File: ' . $classFile, 1399291432);
        }
    }

    /**
     * @param string $classContent
     * @throws ParsingException
     * @return ParsedClass
     */
    public function parseClass($classContent): ParsedClass
    {
        $parsedClass = new ParsedClass();

        $parsedClass->setNamespace($this->parseNamespace($classContent));
        $parsedClass->setClassName($this->parseClassName($classContent));
        $parsedClass->setInterface($this->isInterface($classContent));
        $parsedClass->setAbstract($this->isAbstract($classContent));

        if ($this->parseNamespace($classContent) === '') {
            $parsedClass->setNamespaceSeparator('');
        } else {
            $parsedClass->setNamespaceSeparator($this->parseNamespaceRaw($classContent) ? '\\' : '_');
        }

        return $parsedClass;
    }

    /**
     * @param string $classContent
     * @throws ParsingException
     * @return string
     */
    protected function parseClassName($classContent): string
    {
        $className = $this->parseClassNameRaw($classContent);
        if (!$this->parseNamespaceRaw($classContent)) {
            $classParts = explode('_', $className);
            $className = array_pop($classParts);
        }

        return $className;
    }

    /**
     * @param string $classContent
     * @return string
     */
    protected function parseNamespace($classContent): string
    {
        $phpNamespace = $this->parseNamespaceRaw($classContent);
        if (!$phpNamespace) {
            $className = $this->parseClassNameRaw($classContent);
            $classParts = explode('_', $className);
            array_pop($classParts);
            $namespace = implode('_', $classParts);
        }

        return $namespace ?? $phpNamespace;
    }

    /**
     * @param string $classContent
     * @throws ParsingException
     * @return string
     */
    protected function parseClassNameRaw($classContent): string
    {
        preg_match('/^\\s*(abstract)*\\s*(class|interface) ([a-zA-Z_\x7f-\xff][a-zA-Z0-9\\\\_\x7f-\xff]*)/ims', $classContent, $matches);
        if (!isset($matches[2])) {
            throw new ParsingException('Class file does not contain a class or interface definition', 1399285302);
        }

        return $matches[3];
    }

    /**
     * @param string $classContent
     * @return bool
     */
    protected function isInterface($classContent): bool
    {
        preg_match('/^\\s*interface ([a-zA-Z_\x7f-\xff][a-zA-Z0-9\\\\_\x7f-\xff]*)/ims', $classContent, $matches);

        return isset($matches[1]);
    }

    /**
     * @param string $classContent
     * @return bool
     */
    protected function isAbstract($classContent): bool
    {
        preg_match('/^\\s*(abstract)*\\s*(class|interface) ([a-zA-Z_\x7f-\xff][a-zA-Z0-9\\\\_\x7f-\xff]*)/ims', $classContent, $matches);

        return isset($matches[1]) && trim($matches[1]) === 'abstract';
    }

    /**
     * @param string $classContent
     * @return string
     */
    protected function parseNamespaceRaw($classContent): string
    {
        preg_match('/^\\s*namespace ([^ ;]*)/ims', $classContent, $matches);

        return isset($matches[1]) ? trim($matches[1]) : '';
    }
}
