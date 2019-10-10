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

use Helhum\Typo3Console\Service\CacheService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheListGroupsCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('List cache groups');
        $this->setHelp('Lists all registered cache groups.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cacheService = new CacheService();
        $groups = $cacheService->getValidCacheGroups();
        sort($groups);

        switch (count($groups)) {
            case 0:
                $output->writeln('No cache groups are registered.');
                break;
            case 1:
                $output->writeln('The following cache group is registered: "' . implode('", "', $groups) . '".');
                break;
            default:
                $output->writeln('The following cache groups are registered: "' . implode('", "', $groups) . '".');
                break;
        }

        return 0;
    }
}
