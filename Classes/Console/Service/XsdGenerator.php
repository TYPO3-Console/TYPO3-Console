<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Service;

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

use Helhum\Typo3Console\Parser\ParsingException;
use Helhum\Typo3Console\Parser\PhpParser;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Reflection\DocCommentParser;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition;

/**
 * XML Schema (XSD) Generator. Will generate an XML schema which can be used for auto-completion
 * in schema-aware editors like Eclipse XML editor.
 */
class XsdGenerator
{
    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * The doc comment parser.
     *
     * @var DocCommentParser
     */
    protected $docCommentParser;

    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    public function __construct(
        PackageManager $packageManager,
        ObjectManagerInterface $objectManager,
        DocCommentParser $docCommentParser,
        ReflectionService $reflectionService
    ) {
        $this->packageManager = $packageManager;
        $this->objectManager = $objectManager;
        $this->docCommentParser = $docCommentParser;
        $this->reflectionService = $reflectionService;
    }

    /**
     * Generate the XML Schema definition for a given namespace.
     * It will generate an XSD file for all view helpers in this namespace.
     *
     * @param string $viewHelperNamespace Namespace identifier to generate the XSD for, without leading Backslash.
     * @param string $xsdNamespace $xsdNamespace unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers")
     * @throws Exception
     * @return string XML Schema definition
     */
    public function generateXsd($viewHelperNamespace, $xsdNamespace)
    {
        $viewHelperNamespace = rtrim($viewHelperNamespace, '_\\') . $this->getDelimiterFromNamespace($viewHelperNamespace);
        $classNames = $this->getClassNamesInNamespace($viewHelperNamespace);
        if (count($classNames) === 0) {
            throw new Exception(sprintf('No ViewHelpers found in namespace "%s"', $viewHelperNamespace), 1330029328);
        }

        return $this->generateXsdFromClassNames($classNames, $xsdNamespace);
    }

    /**
     * Generate the XML Schema definition for a given namespace.
     * It will generate an XSD file for all view helpers in this namespace.
     *
     * @param array $viewHelperPaths One or more paths to a view helper class files
     * @param string $xsdNamespace $xsdNamespace unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers")
     * @throws Exception
     * @return string XML Schema definition
     */
    public function generateXsdFromClassFiles(array $viewHelperPaths, $xsdNamespace)
    {
        $classNames = $this->getClassNamesInPaths($viewHelperPaths);
        if (count($classNames) === 0) {
            throw new Exception(sprintf('No ViewHelpers found in paths "%s"', implode(',', $viewHelperPaths)), 1464982249);
        }

        return $this->generateXsdFromClassNames($classNames, $xsdNamespace);
    }

    /**
     * Generate the XML Schema definition for a given namespace.
     * It will generate an XSD file for all view helpers in this namespace.
     *
     * @param array $classNames One or more view helper class names
     * @param string $xsdNamespace $xsdNamespace unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers")
     * @throws Exception
     * @return string XML Schema definition
     */
    protected function generateXsdFromClassNames(array $classNames, $xsdNamespace)
    {
        if (count($classNames) === 0) {
            throw new Exception(sprintf('No ViewHelper classes given'), 1464984856);
        }
        $xmlRootNode = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema" targetNamespace="' . $xsdNamespace . '"></xsd:schema>');

        foreach ($classNames as $className) {
            $this->generateXmlForClassName($className, $xmlRootNode);
        }

        return $xmlRootNode->asXML();
    }

    /**
     * Generate the XML Schema for a given class name.
     *
     * @param string $className Class name to generate the schema for.
     * @param \SimpleXMLElement $xmlRootNode XML root node where the xsd:element is appended.
     * @return void
     */
    protected function generateXmlForClassName($className, \SimpleXMLElement $xmlRootNode)
    {
        $reflectionClass = new \ReflectionClass($className);
        $tagName = $this->getTagNameForClass($className);
        $xsdElement = $xmlRootNode->addChild('xsd:element');
        $xsdElement['name'] = $tagName;
        $this->docCommentParser->parseDocComment($reflectionClass->getDocComment());
        $this->addDocumentation($this->docCommentParser->getDescription(), $xsdElement);

        $xsdComplexType = $xsdElement->addChild('xsd:complexType');
        $xsdComplexType['mixed'] = 'true';
        $xsdSequence = $xsdComplexType->addChild('xsd:sequence');
        $xsdAny = $xsdSequence->addChild('xsd:any');
        $xsdAny['minOccurs'] = '0';
        $xsdAny['maxOccurs'] = 'unbounded';

        $this->addAttributes($className, $xsdComplexType);
    }

    /**
     * Add attribute descriptions to a given tag.
     * Initializes the view helper and its arguments, and then reads out the list of arguments.
     *
     * @param string $className Class name where to add the attribute descriptions
     * @param \SimpleXMLElement $xsdElement XML element to add the attributes to.
     * @return void
     */
    protected function addAttributes($className, \SimpleXMLElement $xsdElement)
    {
        /** @var AbstractViewHelper $viewHelper */
        $viewHelper = $this->objectManager->get($className);
        $argumentDefinitions = $viewHelper->prepareArguments();

        /** @var $argumentDefinition ArgumentDefinition */
        foreach ($argumentDefinitions as $argumentDefinition) {
            $xsdAttribute = $xsdElement->addChild('xsd:attribute');
            $xsdAttribute['type'] = 'xsd:string';
            $xsdAttribute['name'] = $argumentDefinition->getName();
            $this->addDocumentation($argumentDefinition->getDescription(), $xsdAttribute);
            if ($argumentDefinition->isRequired()) {
                $xsdAttribute['use'] = 'required';
            }
        }
    }

    /**
     * Add documentation XSD to a given XML node
     *
     * @param string $documentation Documentation string to add.
     * @param \SimpleXMLElement $xsdParentNode Node to add the documentation to
     * @return void
     */
    protected function addDocumentation($documentation, \SimpleXMLElement $xsdParentNode)
    {
        $xsdAnnotation = $xsdParentNode->addChild('xsd:annotation');
        $this->addChildWithCData($xsdAnnotation, 'xsd:documentation', $documentation);
    }

    /**
     * Get all class names inside this namespace and return them as array.
     * This has to be done by iterating over class files for TYPO3
     * as the Extbase Reflection Service cannot return all implementations
     * of Fluid AbstractViewHelpers
     *
     * @param array $paths
     * @return array Array of all class names inside a given namespace.
     */
    protected function getClassNamesInPaths(array $paths)
    {
        $viewHelperClassFiles = [];
        foreach ($paths as $path) {
            $viewHelperClassFiles = array_merge(
                $viewHelperClassFiles,
                GeneralUtility::getAllFilesAndFoldersInPath(
                    [],
                    $path,
                    'php'
                )
            );
        }
        $affectedViewHelperClassNames = [];
        foreach ($viewHelperClassFiles as $filePathAndFilename) {
            try {
                $potentialViewHelperClassName = $this->getClassNameFromFile($filePathAndFilename);
            } catch (ParsingException $e) {
                continue;
            }
            if (strpos($potentialViewHelperClassName, 'ViewHelpers') === false) {
                continue;
            }
            if (
                is_subclass_of($potentialViewHelperClassName, \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class)
                || is_subclass_of($potentialViewHelperClassName, \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper::class)
            ) {
                if (!class_exists($potentialViewHelperClassName)) {
                    require $filePathAndFilename;
                }
                $classReflection = new \ReflectionClass($potentialViewHelperClassName);
                if ($classReflection->isAbstract() === true) {
                    continue;
                }
                $affectedViewHelperClassNames[] = $potentialViewHelperClassName;
            }
        }
        sort($affectedViewHelperClassNames);

        return $affectedViewHelperClassNames;
    }

    /**
     * Get all class names inside this namespace and return them as array.
     * This has to be done by iterating over class files for TYPO3
     * as the Extbase Reflection Service cannot return all implementations
     * of Fluid AbstractViewHelpers
     *
     * @param string $namespace
     * @return array Array of all class names inside a given namespace.
     */
    protected function getClassNamesInNamespace($namespace)
    {
        $packageKey = $this->getPackageKeyFromNamespace($namespace);
        $viewHelperClassFilePaths[] = $this->packageManager->getPackage($packageKey)->getPackagePath() . 'Classes/ViewHelpers/';
        if ($packageKey === 'fluid') {
            $viewHelperClassFilePaths[] = realpath(PATH_site . 'typo3/') . '/../vendor/typo3fluid/fluid/src/ViewHelpers/';
        }

        return $this->getClassNamesInPaths($viewHelperClassFilePaths);
    }

    /**
     * @param string $namespace
     * @return string
     */
    protected function getPackageKeyFromNamespace($namespace)
    {
        $delimiter = $this->getDelimiterFromNamespace($namespace);
        $namespaceParts = explode($delimiter, $namespace);
        if ($namespaceParts[0] === 'TYPO3' && $namespaceParts[1] === 'CMS') {
            $packageKey = GeneralUtility::camelCaseToLowerCaseUnderscored($namespaceParts[2]);
        } else {
            $packageKey = GeneralUtility::camelCaseToLowerCaseUnderscored($namespaceParts[1]);
        }

        return $packageKey;
    }

    /**
     * @param string $filePath
     * @return string
     */
    protected function getClassNameFromFile($filePath)
    {
        $phpParser = new PhpParser();
        $parsedClass = $phpParser->parseClassFile($filePath);

        return $parsedClass->getFullyQualifiedClassName();
    }

    /**
     * @param string $namespace
     * @return string
     */
    protected function getDelimiterFromNamespace($namespace)
    {
        return strpos($namespace, '\\') === false ? '_' : '\\';
    }

    /**
     * Get a tag name for a given ViewHelper class.
     * Example: For the View Helper TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper, and the
     * namespace prefix TYPO3\CMS\Fluid\ViewHelpers\, this method returns "form.select".
     *
     * @param string $className Class name
     * @return string Tag name
     */
    protected function getTagNameForClass($className)
    {
        /// Strip namespace from the beginning and "ViewHelper" from the end of the class name
        $strippedClassName = substr($className, strpos($className, 'ViewHelpers') + 12, -10);
        $classNameParts = explode('\\', $strippedClassName);

        return implode(
            '.',
            array_map(
                function ($element) {
                    return lcfirst($element);
                },
                $classNameParts
            )
        );
    }

    /**
     * Add a child node to $parentXmlNode, and wrap the contents inside a CDATA section.
     *
     * @param \SimpleXMLElement $parentXmlNode Parent XML Node to add the child to
     * @param string $childNodeName Name of the child node
     * @param string $childNodeValue Value of the child node. Will be placed inside CDATA.
     * @return \SimpleXMLElement the new element
     */
    protected function addChildWithCData(\SimpleXMLElement $parentXmlNode, $childNodeName, $childNodeValue)
    {
        $parentDomNode = dom_import_simplexml($parentXmlNode);
        $domDocument = new \DOMDocument();

        $childNode = $domDocument->appendChild($domDocument->createElement($childNodeName));
        $childNode->appendChild($domDocument->createCDATASection($childNodeValue));
        $childNodeTarget = $parentDomNode->ownerDocument->importNode($childNode, true);
        $parentDomNode->appendChild($childNodeTarget);

        return simplexml_import_dom($childNodeTarget);
    }
}
