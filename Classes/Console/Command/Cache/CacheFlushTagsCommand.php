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
use Helhum\Typo3Console\Service\CacheService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CacheFlushTagsCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Flush cache by tags');
        $this->addArgument(
            'tags',
            InputArgument::REQUIRED,
            'Array of tags (specified as comma separated values) to flush.'
        );
        $this->addOption(
            'groups',
            null,
            null,
            InputOption::VALUE_REQUIRED,
            'Optional array of groups (specified as comma separated values) for which to flush tags. If no group is specified, caches of all groups are flushed.',
            null
        );
        $this->setHelp(
            <<<'EOH'
Flushes caches by tags, optionally only caches in specified groups.

<b>Example:</b> <code>%command.full_name% news_123 --groups pages,all</code>
EOH
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = $this->getApplication();
        if (!$application instanceof Application) {
            throw new \RuntimeException('Fatal error. Application is not properly initialized.', 1546617606);
        }

        $io = new SymfonyStyle($input, $output);
        $application->boot(RunLevel::LEVEL_FULL);

        $tags = GeneralUtility::trimExplode(',', $input->getArgument('tags'), true);
        if ($input->getOption('groups') !== null) {
            $groups = GeneralUtility::trimExplode(',', $input->getOption('groups'), true);
        } else {
            $groups = null;
        }

        try {
            $cacheService = new CacheService();
            $cacheService->flushByTagsAndGroups($tags, $groups);
            if ($groups === null) {
                $io->writeln('Flushed caches by tags "' . implode('","', $tags) . '".');
            } else {
                $io->writeln('Flushed caches by tags "' . implode('","', $tags) . '" in groups: "' . implode('","', $groups) . '".');
            }
        } catch (NoSuchCacheGroupException $e) {
            $io->writeln($e->getMessage());

            return 1;
        }

        return 0;
    }
}
