<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Backend;

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
use Helhum\Typo3Console\Command\RelatableCommandInterface;
use Helhum\Typo3Console\Service\Configuration\ConfigurationService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnlockBackendForEditorsCommand extends AbstractConvertedCommand implements RelatableCommandInterface
{
    private static $LOCK_TYPE_UNLOCKED = 0;

    public function getRelatedCommandNames(): array
    {
        return [
            'typo3_console:backend:lockforeditors',
        ];
    }

    protected function configure()
    {
        $this->setDescription('Unlock backend for editors');
        $this->setHelp('Allow backend access for editors again (e.g. after having been locked with backend:lockforeditors command).');
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
        $configurationService = new ConfigurationService();
        if (!$configurationService->localIsActive('BE/adminOnly')) {
            $output->writeln('<error>The configuration value BE/adminOnly is not modifiable. Is it forced to a value in Additional Configuration?</error>');

            return 2;
        }

        $lockedForEditors = $configurationService->getLocal('BE/adminOnly') !== self::$LOCK_TYPE_UNLOCKED;
        if ($lockedForEditors) {
            $configurationService->setLocal('BE/adminOnly', self::$LOCK_TYPE_UNLOCKED);
            $output->writeln('<info>Unlocked backend for editors.</info>');
        } else {
            $output->writeln('<info>The backend was not locked for editors.</info>');
        }

        return 0;
    }
}
