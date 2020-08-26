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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extensionmanager\Event\AfterExtensionDatabaseContentHasBeenImportedEvent;
use TYPO3\CMS\Extensionmanager\Event\AfterExtensionFilesHaveBeenImportedEvent;
use TYPO3\CMS\Extensionmanager\Event\AfterExtensionStaticDatabaseContentHasBeenImportedEvent;

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
    private static $eventsToRegister = [
        [
            'eventClass' => AfterExtensionFilesHaveBeenImportedEvent::class,
            'resultName' => 'renderExtensionFileImportResult',
        ],
        [
            'eventClass' => AfterExtensionStaticDatabaseContentHasBeenImportedEvent::class,
            'resultName' => 'renderImportedStaticDataResult',
        ],
        [
            'eventClass' => AfterExtensionDatabaseContentHasBeenImportedEvent::class,
            'resultName' => 'renderExtensionDataImportResult',
        ],
        [
            'eventClass' => DatabaseSchemaUpdateEvent::class,
            'resultName' => 'renderSchemaResult',
        ],
    ];

    public function __construct(ExtensionSetupEventDispatcher $eventDispatcher)
    {
        foreach (static::$eventsToRegister as $signalConfig) {
            $eventDispatcher->addListener(
                $signalConfig['eventClass'],
                function ($event) use ($signalConfig) {
                    $this->results[$signalConfig['resultName']][] = $event;
                }
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
        /** @var DatabaseSchemaUpdateEvent $event */
        $event = reset($this->results['renderSchemaResult']);
        if ($event->result->hasPerformedUpdates()) {
            $schemaUpdateResultRenderer = $schemaUpdateResultRenderer ?: new SchemaUpdateResultRenderer();
            $output->outputLine('<info>The following database schema updates were performed:</info>');
            $schemaUpdateResultRenderer->render($event->result, $output, true);
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
        /** @var AfterExtensionStaticDatabaseContentHasBeenImportedEvent $event */
        foreach ($this->results['renderImportedStaticDataResult'] as $event) {
            // Output content of $pathToStaticSqlFile at cli context
            $pathToStaticSqlFile = $event->getSqlFileName();
            $absolutePath = GeneralUtility::getFileAbsFileName($pathToStaticSqlFile);
            if (file_exists($absolutePath)) {
                $output->outputFormatted(
                    '<info>Import content of file "%s" of extension "%s" into database.</info>',
                    [
                        $pathToStaticSqlFile,
                        $event->getPackageKey(),
                    ]
                );
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
        /** @var AfterExtensionFilesHaveBeenImportedEvent $event */
        foreach ($this->results['renderExtensionFileImportResult'] as $event) {
            $output->outputFormatted(
                '<info>Files from extension "%s" were imported to path "%s"</info>',
                [
                    $event->getPackageKey(),
                    PathUtility::stripPathSitePrefix($event->getDestinationAbsolutePath()),
                ]
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
        /** @var AfterExtensionDatabaseContentHasBeenImportedEvent $event */
        foreach ($this->results['renderExtensionDataImportResult'] as $event) {
            $output->outputFormatted(
                '<info>Data from from file "%s" of extension "%s" was imported</info>',
                [
                    $event->getImportFileName(),
                    $event->getPackageKey(),
                ]
            );
        }
    }
}
