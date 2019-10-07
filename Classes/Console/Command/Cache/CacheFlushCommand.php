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

use Helhum\Typo3Console\Command\AbstractConvertedCommand;
use Helhum\Typo3Console\Core\Booting\RunLevel;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Helhum\Typo3Console\Service\CacheLowLevelCleaner;
use Helhum\Typo3Console\Service\CacheService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CacheFlushCommand extends AbstractConvertedCommand
{
    protected function configure()
    {
        $this->setDescription('Flush all caches');
        $this->setHelp(
            <<<'EOH'
Flushes TYPO3 core caches first and after that, flushes caches from extensions.
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
                'files-only',
                null,
                InputOption::VALUE_NONE,
                'Only file caches are flushed'
            ),
        ];
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
            return;
        }

        $lowLevelCleaner->forceFlushDatabaseCacheTables();
        $application->boot(RunLevel::LEVEL_FULL);

        $cacheService = new CacheService();
        $cacheService->flush();
        $cacheService->flushCachesWithDataHandler();

        $io->writeln('Flushed all caches.');
    }

    /**
     * @deprecated will be removed with 6.0
     *
     * @return array
     */
    protected function createDeprecatedDefinition(): array
    {
        return [
            new InputOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Cache is forcibly flushed (low level operations are performed)'
            ),
            new InputArgument(
                'force',
                null,
                'Cache is forcibly flushed (low level operations are performed)',
                false
            ),
            new InputArgument(
                'filesOnly',
                null,
                'Only file caches are flushed',
                false
            ),
        ];
    }

    /**
     * @deprecated will be removed with 6.0
     */
    protected function handleDeprecatedArgumentsAndOptions(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('force')) {
            $io = new SymfonyStyle($input, $output);
            $io->getErrorStyle()->writeln('<warning>Using "--force" is deprecated and has no effect any more.</warning>');
        }
    }
}
