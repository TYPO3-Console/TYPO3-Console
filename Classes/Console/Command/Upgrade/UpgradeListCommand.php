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
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeListCommand extends Command
{
    use EnsureExtensionCompatibilityTrait;

    const OPT_ALL = 'all';

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
        $this->setDescription('List upgrade wizards');
        $this->addOption(
            self::OPT_ALL,
            'a',
            InputOption::VALUE_NONE,
            'If set, all wizards will be listed, even the once marked as ready or done'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        if (!$this->ensureExtensionCompatibility()) {
            return 1;
        }

        $all = $input->getOption(self::OPT_ALL);
        $verbose = $output->isVerbose();

        $wizards = $this->upgradeHandling->executeInSubProcess('listWizards', []);

        $listRenderer = new UpgradeWizardListRenderer();
        $consoleOutput = new ConsoleOutput($output, $input);

        $output->writeln('<comment>Wizards scheduled for execution:</comment>');
        $listRenderer->render($wizards['scheduled'], $consoleOutput, $verbose);

        if ($all) {
            $output->writeln(PHP_EOL . '<comment>Wizards marked as done:</comment>');
            $listRenderer->render($wizards['done'], $consoleOutput, $verbose);
        }
    }
}
