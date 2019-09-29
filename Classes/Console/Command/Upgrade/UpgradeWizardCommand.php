<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Upgrade;

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

use Helhum\Typo3Console\Install\Upgrade\UpgradeHandling;
use Helhum\Typo3Console\Install\Upgrade\UpgradeWizardResultRenderer;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeWizardCommand extends Command
{
    use EnsureExtensionCompatibilityTrait;

    const ARG_IDENTIFIER = 'identifier';
    const OPT_ARGUMENTS = 'arguments';
    const OPT_FORCE = 'force';

    /**
     * @var UpgradeHandling
     */
    private $upgradeHandling;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param UpgradeHandling|null $upgradeHandling
     */
    public function __construct(
        string $name = null,
        UpgradeHandling $upgradeHandling = null
    ) {
        parent::__construct($name);

        $this->upgradeHandling = $upgradeHandling ?? new UpgradeHandling();
    }

    protected function configure()
    {
        $this->setDescription('Execute a single upgrade wizard');
        $this->addArgument(
            self::ARG_IDENTIFIER,
            InputArgument::REQUIRED,
            'Identifier of the wizard that should be executed'
        );
        $this->addOption(
            self::OPT_ARGUMENTS,
            'a',
            InputOption::VALUE_REQUIRED,
            'Arguments for the wizard prefixed with the identifier, e.g. <code>compatibility7Extension[install]=0</code>',
            []
        );
        $this->addOption(
            self::OPT_FORCE,
            'f',
            InputOption::VALUE_NONE,
            'Force execution, even if the wizard has been marked as done'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        if (!$this->ensureExtensionCompatibility()) {
            return 1;
        }

        $identifier = $input->getArgument(self::ARG_IDENTIFIER);
        $arguments = $input->getOption(self::OPT_ARGUMENTS);
        $force = $input->getOption(self::OPT_FORCE);

        $result = $this->upgradeHandling->executeInSubProcess(
            'executeWizard',
            [$identifier, $arguments, $force]
        );

        (new UpgradeWizardResultRenderer())->render([$identifier => $result], new ConsoleOutput($output, $input));
    }
}
