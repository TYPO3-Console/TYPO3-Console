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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeAllCommand extends Command
{
    use EnsureExtensionCompatibilityTrait;

    const OPT_ARGUMENTS = 'arguments';

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
        $this->setDescription('Execute all upgrade wizards that are scheduled for execution');
        $this->addOption(
            self::OPT_ARGUMENTS,
            'a',
            InputOption::VALUE_REQUIRED,
            'Arguments for the wizard prefixed with the identifier, e.g. <code>compatibility7Extension[install]=0</code>; multiple arguments separated with comma',
            []
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        if (!$this->ensureExtensionCompatibility()) {
            return 1;
        }

        $arguments = $input->getArgument(self::ARG_ARGUMENTS);
        $verbose = $output->isVerbose();

        $output->writeln(PHP_EOL . '<i>Initiating TYPO3 upgrade</i>' . PHP_EOL);

        $messages = [];
        $results = $this->upgradeHandling->executeAll($arguments, $this->output, $messages);

        $output->outputLine(PHP_EOL . PHP_EOL . '<i>Successfully upgraded TYPO3 to version %s</i>', [TYPO3_version]);

        if ($verbose) {
            $output->writeln('');
            $output->writeln('<comment>Upgrade report:</comment>');
            (new UpgradeWizardResultRenderer())->render($results, new ConsoleOutput($output, $input));
        }

        $output->writeln('');
        foreach ($messages as $message) {
            $output->writeln($message);
        }
    }
}
