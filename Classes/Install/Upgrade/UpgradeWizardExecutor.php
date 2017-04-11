<?php
namespace Helhum\Typo3Console\Install\Upgrade;

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

use Helhum\Typo3Console\Tests\Unit\Install\Upgrade\Fixture\DummyUpgradeWizard;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Executes a single upgrade wizard
 * Holds the information on possible user interactions
 */
class UpgradeWizardExecutor
{
    /**
     * @var UpgradeWizardFactory
     */
    private $factory;

    public function __construct(UpgradeWizardFactory $factory = null)
    {
        $this->factory = $factory ?: new UpgradeWizardFactory();
    }

    /**
     * @param string $identifier
     * @param array $rawArguments
     * @param bool $force
     * @return UpgradeWizardResult
     */
    public function executeWizard($identifier, array $rawArguments = [], $force = false)
    {
        $upgradeWizard = $this->factory->create($identifier);

        if ($force) {
            $closure = \Closure::bind(function () use ($upgradeWizard) {
                /** @var DummyUpgradeWizard $upgradeWizard here to avoid annoying (and wrong) protected method inspection in PHPStorm */
                $upgradeWizard->markWizardAsDone(0);
            }, null, get_class($upgradeWizard));
            $closure();
        }

        if (!$upgradeWizard->shouldRenderWizard()) {
            return new UpgradeWizardResult(false);
        }

        // OMG really?
        GeneralUtility::_GETset(
            [
                'values' => [
                    $identifier => $this->processRawArguments($identifier, $rawArguments),
                    'TYPO3\\CMS\\Install\\Updates\\' . $identifier => $this->processRawArguments($identifier, $rawArguments),
                ],
            ],
            'install'
        );

        $dbQueries = [];
        $message = '';
        $hasPerformed = $upgradeWizard->performUpdate($dbQueries, $message);

        return new UpgradeWizardResult($hasPerformed, $dbQueries, [$message]);
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function wizardNeedsExecution($identifier)
    {
        $upgradeWizard = $this->factory->create($identifier);
        return $upgradeWizard->shouldRenderWizard();
    }

    private function processRawArguments($identifier, array $rawArguments = [])
    {
        $processedArguments = [];
        foreach ($rawArguments as $argument) {
            parse_str($argument, $processedArgument);
            $processedArguments = array_replace_recursive($processedArguments, $processedArgument);
        }
        $argumentNamespace = str_replace('TYPO3\\CMS\\Install\\Updates\\', '', $identifier);
        return isset($processedArguments[$argumentNamespace]) ? $processedArguments[$argumentNamespace] : $processedArguments;
    }
}
