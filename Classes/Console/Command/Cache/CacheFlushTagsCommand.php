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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CacheFlushTagsCommand extends AbstractConvertedCommand
{
    protected function configure()
    {
        $this->setDescription('Flush cache by tags');
        $this->setHelp(
            <<<'EOH'
Flushes caches by tags, optionally only caches in specified groups.

<b>Example:</b>

  <code>%command.full_name% news_123 --groups pages,all</code>
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
            new InputArgument(
                'tags',
                InputArgument::REQUIRED,
                'Array of tags (specified as comma separated values) to flush.'
            ),
            new InputOption(
                'groups',
                null,
                InputOption::VALUE_REQUIRED,
                'Optional array of groups (specified as comma separated values) for which to flush tags. If no group is specified, caches of all groups are flushed.'
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
                $output->writeln('Flushed caches by tags "' . implode('","', $tags) . '".');
            } else {
                $output->writeln('Flushed caches by tags "' . implode('","', $tags) . '" in groups: "' . implode('","', $groups) . '".');
            }
        } catch (NoSuchCacheGroupException $e) {
            $output->writeln($e->getMessage());

            return 1;
        }

        return 0;
    }
}
