<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Cleanup;

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
use Helhum\Typo3Console\Log\Writer\ConsoleWriter;
use Helhum\Typo3Console\Service\Persistence\PersistenceContext;
use Helhum\Typo3Console\Service\Persistence\PersistenceIntegrityService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UpdateReferenceIndexCommand extends AbstractConvertedCommand
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    protected function configure()
    {
        $this->setDescription('Update reference index');
        $this->setHelp(
            <<<'EOH'
Updates reference index to ensure data integrity

<b>Example:</b>

  <code>%command.full_name% --dry-run --verbose</code>
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
            new InputOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If set, index is only checked without performing any action'
            ),
            new InputOption(
                'show-progress',
                null,
                InputOption::VALUE_NONE,
                'Whether or not to output a progress bar'
            ),
        ];
    }

    /**
     * @deprecated will be removed with 6.0
     */
    protected function handleDeprecatedArgumentsAndOptions(InputInterface $input, OutputInterface $output)
    {
        // nothing to do here
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $persistenceIntegrityService = new PersistenceIntegrityService(
            new PersistenceContext(
                GeneralUtility::makeInstance(ConnectionPool::class),
                $GLOBALS['TCA']
            )
        );
        $this->io = new SymfonyStyle($input, $output);

        $dryRun = $input->getOption('dry-run');
        $showProgress = $input->getOption('show-progress');
        $verbose = $this->io->isVerbose();

        $this->io->writeln(
            '<info>' . ($dryRun ? 'Checking' : 'Updating') . ' reference index. This may take a while …</info>'
        );

        $operation = $dryRun ? 'checkReferenceIndex' : 'updateReferenceIndex';

        list($errorCount, $recordCount, $processedTables) = $persistenceIntegrityService->{$operation}(
            $this->createReferenceIndexDelegateWithOptions($dryRun, $verbose, $showProgress)
        );

        if ($errorCount > 0) {
            $this->io->writeln(sprintf(
                '<info>%d errors were ' . ($dryRun ? 'found' : 'fixed') . ', while '
                    . ($dryRun ? 'checking' : 'updating') . ' reference index for %d records from %d tables.</info>',
                $errorCount,
                $recordCount,
                count($processedTables)
            ));
        } else {
            $this->io->newLine();
            $this->io->writeln('<info>Index integrity was perfect!</info>');
        }

        return 0;
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
            $delegate->subscribeEvent(
                'willStartOperation',
                function ($max) {
                    $this->io->progressStart($max);
                }
            );
            $delegate->subscribeEvent(
                'willUpdateRecord',
                function () {
                    $this->io->progressAdvance();
                }
            );
            $delegate->subscribeEvent(
                'operationHasEnded',
                function () {
                    $this->io->progressFinish();
                }
            );
        }

        if (!$dryRun) {
            $delegate->subscribeEvent(
                'operationHasEnded',
                function () {
                    GeneralUtility::makeInstance(Registry::class)->set(
                        'core',
                        'sys_refindex_lastUpdate',
                        $GLOBALS['EXEC_TIME']
                    );
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

        $options['output'] = $this->io;

        $logger = new Logger(get_class($this));
        $logger->addWriter($verbose ? LogLevel::DEBUG : LogLevel::WARNING, new ConsoleWriter($options));

        return $logger;
    }
}
