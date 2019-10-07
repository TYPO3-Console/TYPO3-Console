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
use Helhum\Typo3Console\Service\CacheService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheListGroupsCommand extends AbstractConvertedCommand
{
    protected function configure()
    {
        $this->setDescription('List cache groups');
        $this->setHelp('Lists all registered cache groups.');
        /** @deprecated Will be removed with 6.0 */
        $this->setDefinition($this->createCompleteInputDefinition());
    }

    /**
     * @deprecated Will be removed with 6.0
     */
    protected function createNativeDefinition(): array
    {
        return [];
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
