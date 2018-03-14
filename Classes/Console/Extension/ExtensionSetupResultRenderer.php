<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Extension;

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

use Helhum\Typo3Console\Database\Schema\SchemaUpdateResultRenderer;
use Helhum\Typo3Console\Database\Schema\SchemaUpdateType;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Helhum\Typo3Console\Service\Database\SchemaService;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * Class to collect and render results of extension setup
 */
class ExtensionSetupResultRenderer
{
    /**
     * @var array
     */
    private $results = [];

    /**
     * Mapping between method name at this object and signal slot
     *
     * @var array
     */
    private static $signalsToRegister = [
        [
            'signalClass' => InstallUtility::class,
            'signalName' => 'afterExtensionFileImport',
            'resultName' => 'renderExtensionFileImportResult',
        ],
        [
            'signalClass' => InstallUtility::class,
            'signalName' => 'afterExtensionStaticSqlImport',
            'resultName' => 'renderImportedStaticDataResult',
        ],
        [
            'signalClass' => InstallUtility::class,
            'signalName' => 'afterExtensionT3DImport',
            'resultName' => 'renderExtensionDataImportResult',
        ],
        [
            'signalClass' => SchemaService::class,
            'signalName' => 'afterDatabaseUpdate',
            'resultName' => 'renderSchemaResult',
        ],
    ];

    public function __construct(Dispatcher $signalSlotDispatcher)
    {
        $collector = $this;
        foreach (static::$signalsToRegister as $signalConfig) {
            $signalSlotDispatcher->connect(
                $signalConfig['signalClass'],
                $signalConfig['signalName'],
                \Closure::bind(function ($result) use ($signalConfig, $collector) {
                    $collector->results[$signalConfig['resultName']][] = $result;
                }, null, $this)
            );
        }
    }

    /**
     * Output schema result
     * @param ConsoleOutput $output
     */
    public function renderSchemaResult(ConsoleOutput $output, SchemaUpdateResultRenderer $schemaUpdateResultRenderer = null)
    {
        if (!isset($this->results['renderSchemaResult'])) {
            return;
        }
        $result = reset($this->results['renderSchemaResult']);
        if ($result->hasPerformedUpdates()) {
            $schemaUpdateResultRenderer = $schemaUpdateResultRenderer ?: new SchemaUpdateResultRenderer();
            $output->outputLine('<info>The following database schema updates were performed:</info>');
            $schemaUpdateResultRenderer->render($result, $output, true);
        } else {
            $output->outputLine(
                '<info>No schema updates were performed for update types:%s</info>',
                [
                    PHP_EOL . '"' . implode('", "', SchemaUpdateType::expandSchemaUpdateTypes(['safe'])) . '"',
                ]
            );
        }
    }

    /**
     * Output at cli context imported static database content.
     *
     * @param ConsoleOutput $output
     */
    public function renderImportedStaticDataResult(ConsoleOutput $output)
    {
        if (!isset($this->results['renderImportedStaticDataResult'])) {
            return;
        }
        foreach ($this->results['renderImportedStaticDataResult'] as $pathToStaticSqlFile) {
            // Output content of $pathToStaticSqlFile at cli context
            $absolutePath = PATH_site . $pathToStaticSqlFile;
            if (file_exists($absolutePath)) {
                $output->outputFormatted('<info>Import content of file "%s" into database.</info>', [$pathToStaticSqlFile]);
                $output->outputFormatted(file_get_contents($absolutePath), [], 2);
            }
        }
    }

    /**
     * Output at cli context initialized imported files to $destinationAbsolutePath
     *
     * @param ConsoleOutput $output
     */
    public function renderExtensionFileImportResult(ConsoleOutput $output)
    {
        if (!isset($this->results['renderExtensionFileImportResult'])) {
            return;
        }
        foreach ($this->results['renderExtensionFileImportResult'] as $destinationAbsolutePath) {
            $output->outputFormatted(
                '<info>Files from extension was imported to path "%s"</info>',
                [PathUtility::stripPathSitePrefix($destinationAbsolutePath)]
            );
        }
    }

    /**
     * Output at cli context initialized imported files to $destinationAbsolutePath
     *
     * @param ConsoleOutput $output
     */
    public function renderExtensionDataImportResult(ConsoleOutput $output)
    {
        if (!isset($this->results['renderExtensionDataImportResult'])) {
            return;
        }
        foreach ($this->results['renderExtensionDataImportResult'] as $importedFile) {
            $output->outputFormatted(
                '<info>Data from from file "%s" was imported</info>',
                [$importedFile]
            );
        }
    }
}
