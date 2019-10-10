<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Documentation;

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

use Helhum\Typo3Console\Command\AbstractConvertedCommand;
use Helhum\Typo3Console\Service;
use Helhum\Typo3Console\Service\XsdGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Reflection\DocCommentParser;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

class GenerateXsdCommand extends AbstractConvertedCommand
{
    protected function configure()
    {
        $this->setDescription('Generate Fluid ViewHelper XSD Schema');
        $this->setHelp(
            <<<'EOH'
Generates Schema documentation (XSD) for your ViewHelpers, preparing the
file to be placed online and used by any XSD-aware editor.
After creating the XSD file, reference it in your IDE and import the namespace
in your Fluid template by adding the xmlns:* attribute(s):
<code><html xmlns="http://www.w3.org/1999/xhtml" xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers" ...></code>
EOH
        );
        /** @deprecated Will be removed with 6.0 */
        $this->setDefinition($this->createCompleteInputDefinition());
    }

    /**
     * @deprecated Will be removed with 6.0
     */
    protected function createNativeDefinition(): array
    {
        return [
            new InputArgument(
                'phpNamespace',
                InputArgument::REQUIRED,
                'Namespace of the Fluid ViewHelpers without leading backslash (for example "TYPO3\Fluid\ViewHelpers" or "Tx_News_ViewHelpers"). NOTE: Quote and/or escape this argument as needed to avoid backslashes from being interpreted!'
            ),
            new InputOption(
                'xsd-namespace',
                '-x',
                InputOption::VALUE_REQUIRED,
                'Unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers"). Defaults to "http://typo3.org/ns/<php namespace>".'
            ),
            new InputOption(
                'target-file',
                '-t',
                InputOption::VALUE_REQUIRED,
                'File path and name of the generated XSD schema. If not specified the schema will be output to standard output.'
            )
        ];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $phpNamespace = $input->getArgument('phpNamespace');
        $xsdNamespace = $input->getOption('xsd-namespace');
        $targetFile = $input->getOption('target-file');
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
        try {
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $xsdGenerator = new XsdGenerator(
                GeneralUtility::makeInstance(PackageManager::class),
                $objectManager,
                GeneralUtility::makeInstance(DocCommentParser::class),
                GeneralUtility::makeInstance(ReflectionService::class)
            );
            $xsdSchema = $xsdGenerator->generateXsd($phpNamespace, $xsdNamespace);
        } catch (Service\Exception $exception) {
            $output->writeln('An error occurred while trying to generate the XSD schema:');
            $output->writeln($exception->getMessage());
            return 1;
        }
        if ($targetFile === null) {
            echo $xsdSchema;
        } else {
            file_put_contents($targetFile, $xsdSchema);
        }

        return 0;
    }

    /**
     * @deprecated will be removed with 6.0
     */
    protected function handleDeprecatedArgumentsAndOptions(InputInterface $input, OutputInterface $output)
    {
        // nothing to do here
    }
}
