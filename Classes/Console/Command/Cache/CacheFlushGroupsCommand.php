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
use Helhum\Typo3Console\Service\CacheService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CacheFlushGroupsCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Flush all caches in specified groups');
        $this->addArgument(
            'groups',
            InputArgument::REQUIRED,
            'An array of names (specified as comma separated values) of cache groups to flush'
        );
        $this->setHelp(
            <<<'EOH'
Flushes all caches in specified groups.
Valid group names are by default:

- lowlevel
- pages
- system

<b>Example:</b> <code>%command.full_name% pages,all</code>
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

        $groups = GeneralUtility::trimExplode(',', $input->getArgument('groups'), true);

        try {
            $cacheService = new CacheService();
            $cacheService->flushGroups($groups);
        } catch (NoSuchCacheGroupException $e) {
            $io->writeln($e->getMessage());

            return 1;
        }

        $io->writeln('Flushed all caches for group(s): "' . implode('","', $groups) . '".');

        return 0;
    }
}
