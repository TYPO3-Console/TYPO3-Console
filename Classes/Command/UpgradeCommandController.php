<?php
namespace Helhum\Typo3Console\Command;

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
use Helhum\Typo3Console\Install\Upgrade\UpgradeWizardListRenderer;
use Helhum\Typo3Console\Install\Upgrade\UpgradeWizardResultRenderer;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Controller\CommandController;

class UpgradeCommandController extends CommandController
{
    /**
     * @var UpgradeHandling
     */
    private $upgradeHandling;

    /**
     * @var CommandDispatcher
     */
    private $commandDispatcher;

    /**
     * @param UpgradeHandling|null $upgradeHandling
     * @param CommandDispatcher|null $commandDispatcher
     */
    public function __construct(
        UpgradeHandling $upgradeHandling = null,
        CommandDispatcher $commandDispatcher = null
    ) {
        $this->upgradeHandling = $upgradeHandling ?: new UpgradeHandling();
        $this->commandDispatcher = $commandDispatcher ?: CommandDispatcher::createFromCommandRun();
    }

    /**
     * List upgrade wizards
     *
     * @param bool $verbose If set, a more verbose description for each wizard is shown, if not set only the title is shown
     * @param bool $all If set, all wizards will be listed, even the once marked as ready or done
     */
    public function listCommand($verbose = false, $all = false)
    {
        $wizards = $this->upgradeHandling->executeInSubProcess('listWizards');

        $listRenderer = new UpgradeWizardListRenderer();
        $this->outputLine('<comment>Wizards scheduled for execution:</comment>');
        $listRenderer->render($wizards['scheduled'], $this->output, $verbose);

        if ($all) {
            $this->outputLine(PHP_EOL . '<comment>Wizards marked as done:</comment>');
            $listRenderer->render($wizards['done'], $this->output, $verbose);
        }
    }

    /**
     * Execute a single upgrade wizard
     *
     * @param string $identifier Identifier of the wizard that should be executed
     * @param array $arguments Arguments for the wizard prefixed with the identifier, e.g. <code>compatibility7Extension[install]=0</code>
     * @param bool $force Force execution, even if the wizard has been marked as done
     */
    public function wizardCommand($identifier, array $arguments = [], $force = false)
    {
        $result = $this->upgradeHandling->executeInSubProcess('executeWizard', [$identifier, $arguments, $force]);
        (new UpgradeWizardResultRenderer())->render([$identifier => $result], $this->output);
    }

    /**
     * Execute all upgrade wizards that are scheduled for execution
     *
     * @param array $arguments Arguments for the wizard prefixed with the identifier, e.g. <code>compatibility7Extension[install]=0</code>; multiple arguments separated with comma
     * @param bool $verbose If set, output of the wizards will be shown, including all SQL Queries that were executed
     */
    public function allCommand(array $arguments = [], $verbose = false)
    {
        $this->outputLine('<i>Initiating TYPO3 upgrade</i>' . PHP_EOL);

        $results = $this->upgradeHandling->executeAll($arguments, $this->output);

        $this->outputLine(PHP_EOL . PHP_EOL . '<i>Successfully upgraded TYPO3 to version %s</i>', [TYPO3_version]);

        if ($verbose) {
            $this->outputLine();
            $this->outputLine('<comment>Upgrade report:</comment>');
            (new UpgradeWizardResultRenderer())->render($results, $this->output);
        }
    }

    /**
     * This is where the hard work happens in a fully bootstrapped TYPO3
     * It will be called as sub process
     *
     * @param string $command
     * @param string $arguments Serialized arguments
     * @internal
     */
    public function subProcessCommand($command, $arguments)
    {
        $arguments = unserialize($arguments);
        $result = call_user_func_array([$this->upgradeHandling, $command], $arguments);
        $this->output(serialize($result));
    }
}
