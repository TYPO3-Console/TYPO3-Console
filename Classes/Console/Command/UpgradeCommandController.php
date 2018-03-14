<?php
declare(strict_types=1);
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

use Helhum\Typo3Console\Annotation\Command\Definition;
use Helhum\Typo3Console\Install\Upgrade\UpgradeHandling;
use Helhum\Typo3Console\Install\Upgrade\UpgradeWizardListRenderer;
use Helhum\Typo3Console\Install\Upgrade\UpgradeWizardResultRenderer;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Package\Exception\UnknownPackageException;

class UpgradeCommandController extends CommandController
{
    /**
     * @var UpgradeHandling
     */
    private $upgradeHandling;

    /**
     * @param UpgradeHandling|null $upgradeHandling
     */
    public function __construct(
        UpgradeHandling $upgradeHandling = null
    ) {
        $this->upgradeHandling = $upgradeHandling ?: new UpgradeHandling();
    }

    /**
     * Check TYPO3 version constraints of extensions
     *
     * This command is especially useful **before** switching sources to a new TYPO3 version.
     * It checks the version constraints of all third party extensions against a given TYPO3 version.
     * It therefore relies on the constraints to be correct.
     *
     * @param array $extensionKeys Extension keys to check. Separate multiple extension keys with comma.
     * @param string $typo3Version TYPO3 version to check against. Defaults to current TYPO3 version.
     * @Definition\Argument(name="extensionKeys")
     */
    public function checkExtensionConstraintsCommand(array $extensionKeys = [], $typo3Version = TYPO3_version)
    {
        if (empty($extensionKeys)) {
            $failedPackageMessages = $this->upgradeHandling->matchAllExtensionConstraints($typo3Version);
        } else {
            $failedPackageMessages = [];
            foreach ($extensionKeys as $extensionKey) {
                try {
                    if (!empty($result = $this->upgradeHandling->matchExtensionConstraints($extensionKey, $typo3Version))) {
                        $failedPackageMessages[$extensionKey] = $result;
                    }
                } catch (UnknownPackageException $e) {
                    $this->outputLine('<warning>Extension "%s" is not found in the system</warning>', [$extensionKey]);
                }
            }
        }
        foreach ($failedPackageMessages as $constraintMessage) {
            $this->outputLine('<error>%s</error>', [$constraintMessage]);
        }
        if (empty($failedPackageMessages)) {
            $this->outputLine('<info>All third party extensions claim to be compatible with TYPO3 version %s</info>', [$typo3Version]);
        } else {
            $this->quit(1);
        }
    }

    /**
     * List upgrade wizards
     *
     * @param bool $all If set, all wizards will be listed, even the once marked as ready or done
     */
    public function listCommand($all = false)
    {
        $verbose = $this->output->getSymfonyConsoleOutput()->isVerbose();
        $messages = [];
        $wizards = $this->upgradeHandling->executeInSubProcess('listWizards', [], $messages);

        $listRenderer = new UpgradeWizardListRenderer();
        $this->outputLine('<comment>Wizards scheduled for execution:</comment>');
        $listRenderer->render($wizards['scheduled'], $this->output, $verbose);

        if ($all) {
            $this->outputLine(PHP_EOL . '<comment>Wizards marked as done:</comment>');
            $listRenderer->render($wizards['done'], $this->output, $verbose);
        }
        $this->outputLine();
        foreach ($messages as $message) {
            $this->outputLine($message);
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
        $messages = [];
        $result = $this->upgradeHandling->executeInSubProcess('executeWizard', [$identifier, $arguments, $force], $messages);
        (new UpgradeWizardResultRenderer())->render([$identifier => $result], $this->output);
        $this->outputLine();
        foreach ($messages as $message) {
            $this->outputLine($message);
        }
    }

    /**
     * Execute all upgrade wizards that are scheduled for execution
     *
     * @param array $arguments Arguments for the wizard prefixed with the identifier, e.g. <code>compatibility7Extension[install]=0</code>; multiple arguments separated with comma
     */
    public function allCommand(array $arguments = [])
    {
        $verbose = $this->output->getSymfonyConsoleOutput()->isVerbose();
        $this->outputLine(PHP_EOL . '<i>Initiating TYPO3 upgrade</i>' . PHP_EOL);

        $messages = [];
        $results = $this->upgradeHandling->executeAll($arguments, $this->output, $messages);

        $this->outputLine(PHP_EOL . PHP_EOL . '<i>Successfully upgraded TYPO3 to version %s</i>', [TYPO3_version]);

        if ($verbose) {
            $this->outputLine();
            $this->outputLine('<comment>Upgrade report:</comment>');
            (new UpgradeWizardResultRenderer())->render($results, $this->output);
        }
        $this->outputLine();
        foreach ($messages as $message) {
            $this->outputLine($message);
        }
    }

    /**
     * This is where the hard work happens in a fully bootstrapped TYPO3
     * It will be called as sub process
     *
     * @param string $upgradeCommand
     * @param string $arguments Serialized arguments
     * @internal
     */
    public function subProcessCommand($upgradeCommand, $arguments)
    {
        $arguments = unserialize($arguments, ['allowed_classes' => false]);
        $result = $this->upgradeHandling->$upgradeCommand(...$arguments);
        $this->output(serialize($result));
    }

    /**
     * Checks for broken extensions
     *
     * This command in meant to be executed as sub process as it is is subject to cause fatal errors
     * when extensions have broken (incompatible) code
     *
     * @param string $extensionKey Extension key for extension to check
     * @param bool $configOnly
     * @internal
     */
    public function checkExtensionCompatibilityCommand($extensionKey, $configOnly = false)
    {
        $this->output(\json_encode($this->upgradeHandling->isCompatible($extensionKey, $configOnly)));
    }
}
