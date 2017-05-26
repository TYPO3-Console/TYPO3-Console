<?php
namespace Helhum\Typo3Console\Command;

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

use Helhum\Typo3Console\Core\ConsoleBootstrap;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Helhum\Typo3Console\Service;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\FluidSchemaGenerator\SchemaGenerator;

/**
 * Command controller for Fluid documentation rendering
 *
 */
class DocumentationCommandController extends CommandController implements SingletonInterface
{
    /**
     * Generate Fluid ViewHelper XSD Schema
     *
     * Generates Schema documentation (XSD) for your ViewHelpers, preparing the
     * file to be placed online and used by any XSD-aware editor.
     * After creating the XSD file, reference it in your IDE and import the namespace
     * in your Fluid template by adding the xmlns:* attribute(s):
     * <code><html xmlns="http://www.w3.org/1999/xhtml" xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers" ...></code>
     *
     * To pass multiple namespaces (which you will need to do when creating XSD for
     * the TYPO3 core ViewHelpers), specify those namespaces as a comma separated list:
     *
     * <code>./vendor/bin/typo3cms documentation:generatexsd --php-namespaces \\TYPO3Fluid\\Fluid\\,\\TYPO3\\CMS\\Fluid
     *
     * Note that the "ViewHelpers" namespace suffix is added automatically and does
     * not need to be provided.
     *
     * @param array $phpNamespace List of namespaces of the Fluid ViewHelpers without leading backslash (for example 'TYPO3\Fluid\ViewHelpers' or 'Tx_News_ViewHelpers'). NOTE: Quote and/or escape this argument as needed to avoid backslashes from being interpreted!
     * @param string $xsdNamespace Unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers"). Defaults to "http://typo3.org/ns/<php namespace>".
     * @param string $targetFile File path and name of the generated XSD schema. If not specified the schema will be output to standard output.
     * @return void
     */
    public function generateXsdCommand(array $phpNamespaces, $targetFile = null)
    {
        $generator = new SchemaGenerator();
        $xsdSchema = '';
        $prefixes = ConsoleBootstrap::getInstance()->getClassLoader()->getPrefixesPsr4();
        $namespaceClassPathMap = [];
        foreach ($phpNamespaces as $namespace) {
            $namespace = trim($namespace, '\\') . '\\';
            $namespace = str_replace('\\ViewHelpers', '', $namespace);

            $classesPath = $prefixes[$namespace][0];
            $namespace = rtrim($namespace, '\\') . '\\ViewHelpers\\';
            $namespaceClassPathMap[$namespace] = realpath($classesPath . '/ViewHelpers/');
        }
        try {
            /** @var ObjectManagerInterface $objectManager */
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $xsdSchema = $generator->generateXsd(
                $namespaceClassPathMap,
                function ($className, ...$arguments) use ($objectManager) {
                    return $objectManager->get($className, ...$arguments);
                }
            );
        } catch (Service\Exception $exception) {
            $this->outputLine('An error occurred while trying to generate the XSD schema:');
            $this->outputLine('%s', [$exception->getMessage()]);
            $this->sendAndExit(1);
        }
        if ($targetFile === null) {
            echo $xsdSchema;
        } else {
            file_put_contents($targetFile, $xsdSchema);
        }
    }
}
