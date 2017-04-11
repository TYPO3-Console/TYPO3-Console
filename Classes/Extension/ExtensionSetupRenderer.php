<?php
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

use Helhum\Typo3Console\Database\Schema\SchemaUpdateResult;
use Helhum\Typo3Console\Database\Schema\SchemaUpdateResultRenderer;
use Helhum\Typo3Console\Database\Schema\SchemaUpdateType;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Helhum\Typo3Console\Service\Database\SchemaService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * Class to output changes from extension setup
 */
class ExtensionSetupRenderer
{
    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var Dispatcher
     */
    private $signalSlotDispatcher;

    /**
     * @var SchemaUpdateResultRenderer
     */
    private $schemaUpdateResultRenderer;

    /**
     * Mapping between method name at this object and signal slot
     *
     * @var array
     */
    private static $methodToSlotMapping = [
        'outputExtensionFileImport' => [InstallUtility::class, 'afterExtensionFileImport'],
        'outputImportedStaticData' => [InstallUtility::class, 'afterExtensionStaticSqlImport'],
        'outputExtensionDataImport' => [InstallUtility::class, 'afterExtensionT3DImport'],
        'outputSchemaResult' => [SchemaService::class, 'afterDatabaseUpdate'],
    ];

    public function __construct(
        ConsoleOutput $output,
        Dispatcher $signalSlotDispatcher,
        SchemaUpdateResultRenderer $schemaUpdateResultRenderer = null
    ) {
        $this->output = $output;
        $this->signalSlotDispatcher = $signalSlotDispatcher;
        $this->schemaUpdateResultRenderer = $schemaUpdateResultRenderer ?: GeneralUtility::makeInstance(ObjectManager::class)->get(SchemaUpdateResultRenderer::class);
    }

    /**
     * Output schema result
     *
     * @param SchemaUpdateResult $result
     */
    public function outputSchemaResult($result)
    {
        if ($result->hasPerformedUpdates()) {
            $this->output->outputLine('<info>The following database schema updates were performed:</info>');
            $this->schemaUpdateResultRenderer->render($result, $this->output, true);
        } else {
            $this->output->outputLine(
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
     * @param string $pathToStaticSqlFile
     * @return void
     */
    public function outputImportedStaticData($pathToStaticSqlFile)
    {
        // Output content of $pathToStaticSqlFile at cli context
        $absolutePath = PATH_site . $pathToStaticSqlFile;
        if (file_exists($absolutePath)) {
            $this->output->outputFormatted('<info>Import content of file "%s" into database.</info>', [$pathToStaticSqlFile]);
            $this->output->outputFormatted(file_get_contents($absolutePath), [], 2);
        }
    }

    /**
     * Output at cli context initialized imported files to $destinationAbsolutePath
     *
     * @param string $destinationAbsolutePath
     * @return void
     */
    public function outputExtensionFileImport($destinationAbsolutePath)
    {
        $this->output->outputFormatted(
            '<info>Files from extension was imported to path "%s"</info>',
            [$destinationAbsolutePath]
        );
    }

    /**
     * Output at cli context initialized imported files to $destinationAbsolutePath
     *
     * @param string $importedFile
     * @return void
     */
    public function outputExtensionDataImport($importedFile)
    {
        $this->output->outputFormatted(
            '<info>Data from from file "%s" was imported</info>',
            [$importedFile]
        );
    }

    public function activateSignalSlots()
    {
        foreach (static::$methodToSlotMapping as $methodName => $slot) {
            $this->signalSlotDispatcher->connect($slot[0], $slot[1], $this, $methodName);
        }
    }
}
