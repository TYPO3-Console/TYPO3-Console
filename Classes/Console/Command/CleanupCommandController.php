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

use Helhum\Typo3Console\Command\Delegation\ReferenceIndexUpdateDelegate;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Helhum\Typo3Console\Service\Persistence\PersistenceIntegrityService;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CleanupCommandController extends CommandController
{
    /**
     * @var PersistenceIntegrityService
     */
    protected $persistenceIntegrityService;

    public function __construct(PersistenceIntegrityService $persistenceIntegrityService)
    {
        $this->persistenceIntegrityService = $persistenceIntegrityService;
    }

    /**
     * Update reference index
     *
     * Updates reference index to ensure data integrity
     *
     * <b>Example:</b> <code>%command.full_name% --dry-run --verbose</code>
     *
     * @param bool $dryRun If set, index is only checked without performing any action
     * @param bool $showProgress Whether or not to output a progress bar
     */
    public function updateReferenceIndexCommand($dryRun = false, $showProgress = false)
    {
        $verbose = $this->output->getSymfonyConsoleOutput()->isVerbose();
        $this->outputLine('<info>' . ($dryRun ? 'Checking' : 'Updating') . ' reference index. This may take a while …</info>');

        $operation = $dryRun ? 'checkReferenceIndex' : 'updateReferenceIndex';

        list($errorCount, $recordCount, $processedTables) = $this->persistenceIntegrityService->{$operation}(
            $this->createReferenceIndexDelegateWithOptions($dryRun, $verbose, $showProgress)
        );

        if ($errorCount > 0) {
            $this->outputLine('<info>%d errors were ' . ($dryRun ? 'found' : 'fixed') . ', while ' . ($dryRun ? 'checking' : 'updating') . ' reference index for %d records from %d tables.</info>', [$errorCount, $recordCount, count($processedTables)]);
        } else {
            $this->outputLine();
            $this->outputLine('<info>Index integrity was perfect!</info>');
        }
    }

    /**
     * @param bool $dryRun
     * @param bool $verbose
     * @param bool $showProgress
     * @return ReferenceIndexUpdateDelegate
     */
    protected function createReferenceIndexDelegateWithOptions($dryRun, $verbose, $showProgress)
    {
        $delegate = new ReferenceIndexUpdateDelegate($this->createLogger($verbose, $showProgress));
        if ($showProgress) {
            $output = $this->output;
            $delegate->subscribeEvent(
                'willStartOperation',
                function ($max) use ($output) {
                    $output->progressStart($max);
                }
            );
            $delegate->subscribeEvent(
                'willUpdateRecord',
                function () use ($output) {
                    $output->progressAdvance();
                }
            );
            $delegate->subscribeEvent(
                'operationHasEnded',
                function () use ($output) {
                    $output->progressFinish();
                }
            );
        }

        if (!$dryRun) {
            $delegate->subscribeEvent(
                'operationHasEnded',
                function () {
                    GeneralUtility::makeInstance(\TYPO3\CMS\Core\Registry::class)->set('core', 'sys_refindex_lastUpdate', $GLOBALS['EXEC_TIME']);
                }
            );
        }

        return $delegate;
    }

    /**
     * @param bool $verbose
     * @param bool $addNewLines
     * @return LoggerInterface
     */
    protected function createLogger($verbose, $addNewLines = false)
    {
        $options = [];
        if ($addNewLines) {
            $options['messageWrap'] = LF . LF . '|' . LF;
        }

        return parent::createDefaultLogger($verbose ? LogLevel::DEBUG : LogLevel::WARNING, $options);
    }
}
