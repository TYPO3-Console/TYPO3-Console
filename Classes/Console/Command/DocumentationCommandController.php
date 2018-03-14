<?php
declare(strict_types=1);
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

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Helhum\Typo3Console\Service;
use Helhum\Typo3Console\Service\XsdGenerator;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Command controller for Fluid documentation rendering
 */
class DocumentationCommandController extends CommandController implements SingletonInterface
{
    /**
     * @var XsdGenerator
     */
    protected $xsdGenerator;

    public function __construct(XsdGenerator $xsdGenerator)
    {
        $this->xsdGenerator = $xsdGenerator;
    }

    /**
     * Generate Fluid ViewHelper XSD Schema
     *
     * Generates Schema documentation (XSD) for your ViewHelpers, preparing the
     * file to be placed online and used by any XSD-aware editor.
     * After creating the XSD file, reference it in your IDE and import the namespace
     * in your Fluid template by adding the xmlns:* attribute(s):
     * <code><html xmlns="http://www.w3.org/1999/xhtml" xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers" ...></code>
     *
     * @param string $phpNamespace Namespace of the Fluid ViewHelpers without leading backslash (for example 'TYPO3\Fluid\ViewHelpers' or 'Tx_News_ViewHelpers'). NOTE: Quote and/or escape this argument as needed to avoid backslashes from being interpreted!
     * @param string $xsdNamespace Unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers"). Defaults to "http://typo3.org/ns/<php namespace>".
     * @param string $targetFile File path and name of the generated XSD schema. If not specified the schema will be output to standard output.
     * @return void
     */
    public function generateXsdCommand($phpNamespace, $xsdNamespace = null, $targetFile = null)
    {
        if ($xsdNamespace === null) {
            $phpNamespace = rtrim($phpNamespace, '_\\');
            if (strpos($phpNamespace, '\\') === false) {
                $search = ['Tx_', '_'];
                $replace = ['', '/'];
            } else {
                $search = '\\';
                $replace = '/';
            }
            $xsdNamespace = sprintf('http://typo3.org/ns/%s', str_replace($search, $replace, $phpNamespace));
        }
        $xsdSchema = '';
        try {
            $xsdSchema = $this->xsdGenerator->generateXsd($phpNamespace, $xsdNamespace);
        } catch (Service\Exception $exception) {
            $this->outputLine('An error occurred while trying to generate the XSD schema:');
            $this->outputLine('%s', [$exception->getMessage()]);
            $this->quit(1);
        }
        if ($targetFile === null) {
            echo $xsdSchema;
        } else {
            file_put_contents($targetFile, $xsdSchema);
        }
    }
}
