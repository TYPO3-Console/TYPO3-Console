<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Cache;

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

use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Helhum\Typo3Console\Service\CacheLowLevelCleaner;
use Helhum\Typo3Console\Service\CacheService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\ClearCacheService;

class CacheFlushCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Flush all TYPO3 caches');
        $this->setHelp(
            <<<'EOH'
Flushes all TYPO3 caches. Opcode cache will not be flushed.
EOH
        );
        $this->setDefinition(
            [
                new InputOption(
                    'files-only',
                    null,
                    InputOption::VALUE_NONE,
                    'Only file caches are flushed. Useful when TYPO3 is not set up or DB connection can not be established.'
                ),
            ]
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = $this->getApplication();
        if (!$application instanceof Application) {
            throw new \RuntimeException('Fatal error. Application is not properly initialized.', 1546617606);
        }
        $io = new SymfonyStyle($input, $output);
        $filesOnly = $input->getOption('files-only') || !$application->isFullyCapable();
        if ($filesOnly) {
            $lowLevelCleaner = new CacheLowLevelCleaner();
            $lowLevelCleaner->forceFlushCachesFiles();
            $io->writeln('Flushed all file caches.');
            // No need to proceed, as files only flush is requested
            return 0;
        }

        $coreCacheService = GeneralUtility::makeInstance(ClearCacheService::class);
        $coreCacheService->clearAll();
        $cacheService = new CacheService();
        $cacheService->flushCachesWithDataHandler();
        $io->writeln('Flushed all caches.');

        return 0;
    }
}
