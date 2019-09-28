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

use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Helhum\Typo3Console\Service\CacheLowLevelCleaner;
use Helhum\Typo3Console\Service\CacheService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CacheFlushCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Flush all caches');
        $this->setHelp(
            <<<'EOH'
Flushes TYPO3 core caches first and after that, flushes caches from extensions.
EOH
        );
        $this->addOption(
            'files-only',
            null,
            InputOption::VALUE_NONE,
            'Only file caches are flushed'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filesOnly = $input->getOption('files-only');
        $application = $this->getApplication();
        if (!$application instanceof Application) {
            throw new \RuntimeException('Fatal error. Application is not properly initialized.', 1546617606);
        }
        $filesOnly = $filesOnly || !$application->isFullyCapable();

        $io = new SymfonyStyle($input, $output);

        $lowLevelCleaner = new CacheLowLevelCleaner();
        $lowLevelCleaner->forceFlushCachesFiles();
        if ($filesOnly) {
            $io->writeln('Flushed all file caches.');
            // No need to proceed, as files only flush is requested
            return 0;
        }

        $lowLevelCleaner->forceFlushDatabaseCacheTables();
        $application->boot(RunLevel::LEVEL_FULL);

        $cacheService = new CacheService();
        $cacheService->flush();
        $cacheService->flushCachesWithDataHandler();

        $io->writeln('Flushed all caches.');

        return 0;
    }
}
