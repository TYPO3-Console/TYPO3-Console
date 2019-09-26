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

use Helhum\Typo3Console\Service\Configuration\ConfigurationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class LockForEditorsCommand extends Command
{
    private const LOCK_TYPE_UNLOCKED = 0;
    private const LOCK_TYPE_ADMIN = 2;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    public function __construct(string $name = null, ConfigurationService $configurationService = null)
    {
        parent::__construct($name);
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->configurationService = $configurationService ?? $objectManager->get(ConfigurationService::class);
    }

    protected function configure()
    {
        $this->setDescription('Lock backend for editors');
        $this->setHelp('Deny backend access, but only for editors.
Admins will still be able to log in and work with the backend.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        if (!$this->configurationService->localIsActive('BE/adminOnly')) {
            $io->error('The configuration value BE/adminOnly is not modifiable. Is it forced to a value in Additional Configuration?');

            return 2;
        }

        $lockedForEditors = $this->configurationService->getLocal('BE/adminOnly') !== self::LOCK_TYPE_UNLOCKED;
        if (!$lockedForEditors) {
            $this->configurationService->setLocal('BE/adminOnly', self::LOCK_TYPE_ADMIN);
            $io->success('Locked backend for editor access.');
        } else {
            $io->warning('The backend was already locked for editors, hence nothing was done.');
        }
    }
}
