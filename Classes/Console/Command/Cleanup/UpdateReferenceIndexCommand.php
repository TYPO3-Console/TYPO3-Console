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

use Helhum\Typo3Console\Log\Writer\ConsoleWriter;
use Helhum\Typo3Console\Service\Persistence\PersistenceIntegrityService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class UpdateReferenceIndexCommand extends Command
{
    const OPT_DRY_RUN = 'dry-run';
    const OPT_SHOW_PROGRESS = 'show-progress';

    /**
     * @var PersistenceIntegrityService
     */
    private $persistenceIntegrityService;

    /**
     * @var SymfonyStyle
     */
    private $io;

    public function __construct(string $name = null, PersistenceIntegrityService $persistenceIntegrityService = null)
    {
        parent::__construct($name);
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->persistenceIntegrityService = $persistenceIntegrityService
            ?? $objectManager->get(PersistenceIntegrityService::class);
    }

    protected function configure()
    {
        $this->setDescription('Update reference index');
        $this->setHelp(
            <<<'EOH'
Updates reference index to ensure data integrity

<b>Example:</b> <code>%command.full_name% --dry-run --verbose</code>
EOH
        );
        $this->addOption(
            self::OPT_DRY_RUN,
            null,
            InputOption::VALUE_NONE,
            'If set, index is only checked without performing any action'
        );
        $this->addOption(
            self::OPT_SHOW_PROGRESS,
            null,
            InputOption::VALUE_NONE,
            'Whether or not to output a progress bar'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $dryRun = $input->getOption(self::OPT_DRY_RUN);
        $showProgress = $input->getOption(self::OPT_SHOW_PROGRESS);
        $verbose = $this->io->isVerbose();

        $this->io->writeln(
            '<info>' . ($dryRun ? 'Checking' : 'Updating') . ' reference index. This may take a while …</info>'
        );

        $operation = $dryRun ? 'checkReferenceIndex' : 'updateReferenceIndex';

        list($errorCount, $recordCount, $processedTables) = $this->persistenceIntegrityService->{$operation}(
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
            $io = $this->io;
            $delegate->subscribeEvent(
                'willStartOperation',
                function ($max) use ($io) {
                    $io->progressStart($max);
                }
            );
            $delegate->subscribeEvent(
                'willUpdateRecord',
                function () use ($io) {
                    $io->progressAdvance();
                }
            );
            $delegate->subscribeEvent(
                'operationHasEnded',
                function () use ($io) {
                    $io->progressFinish();
                }
            );
        }

        if (!$dryRun) {
            $delegate->subscribeEvent(
                'operationHasEnded',
                function () {
                    GeneralUtility::makeInstance(\TYPO3\CMS\Core\Registry::class)->set(
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
