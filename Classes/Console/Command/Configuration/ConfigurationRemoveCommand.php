<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Configuration;

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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ConfigurationRemoveCommand extends Command
{
    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * @var SymfonyStyle
     */
    private $io;

    public function __construct(string $name = null, ConfigurationService $configurationService = null)
    {
        parent::__construct($name);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->configurationService = $configurationService
            ?? $objectManager->get(ConfigurationService::class);
    }

    protected function configure()
    {
        $this->setDescription('Remove configuration option');
        $this->setHelp(
            <<<'EOH'
Removes a system configuration option by path.

For this command to succeed, the configuration option(s) must be in
LocalConfiguration.php and not be overridden elsewhere.

<b>Example:</b> <code>%command.full_name% DB,EXT/EXTCONF/realurl</code>
EOH
        );
        $this->addArgument(
            'paths',
            InputArgument::REQUIRED,
            'Path to system configuration that should be removed. Multiple paths can be specified separated by comma'
        );
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'If set, does not ask for confirmation'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $paths = (array)$input->getArgument('paths');
        $force = $input->getOption('force');

        foreach ($paths as $path) {
            if (!$this->configurationService->localIsActive($path)) {
                $this->io->writeln(sprintf(
                    '<warning>It seems that configuration for path "%s" is overridden.</warning>',
                    $path
                ));
                $this->io->writeln('<warning>Removing the new value might have no effect.</warning>');
            }
            if (!$force && $this->configurationService->hasLocal($path)) {
                $reallyDelete = $this->io->askConfirmation('Remove ' . $path . ' from system configuration (TYPO3_CONF_VARS)? (yes/<b>no</b>): ', false);
                if (!$reallyDelete) {
                    continue;
                }
            }
            $removed = $this->configurationService->removeLocal($path);
            if ($removed) {
                $this->io->writeln(sprintf(
                    '<info>Removed "%s" from system configuration.</info>',
                    $path
                ));
            } else {
                $this->io->writeln(sprintf(
                    '<warning>Path "%s" seems invalid or empty. Nothing done!</warning>',
                    $path
                ));
            }
        }
    }
}
