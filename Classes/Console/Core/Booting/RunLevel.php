<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Core\Booting;

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

use Symfony\Component\Console\Exception\InvalidArgumentException;
use TYPO3\CMS\Core\Core\Bootstrap;

class RunLevel
{
    const LEVEL_ESSENTIAL = 'buildEssentialSequence';
    const LEVEL_COMPILE = 'buildEssentialSequence';
    const LEVEL_MINIMAL = 'buildBasicRuntimeSequence';
    const LEVEL_FULL = 'buildExtendedRuntimeSequence';

    /**
     * @var array
     */
    private $commandOptions = [];

    /**
     * @var array
     */
    private $executedSteps = [];

    /**
     * @var Bootstrap
     */
    private $bootstrap;

    /**
     * @var StepFailedException
     */
    private $error;

    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * @param string $commandIdentifier
     * @param string $runLevel
     * @internal
     */
    public function setRunLevelForCommand(string $commandIdentifier, string $runLevel)
    {
        if (!isset($this->commandOptions[$commandIdentifier]['runLevel'])) {
            $this->commandOptions[$commandIdentifier]['runLevel'] = $runLevel;
        }
    }

    /**
     * @param string $commandIdentifier
     * @param string $stepIdentifier
     * @internal
     */
    public function addBootingStepForCommand(string $commandIdentifier, string $stepIdentifier)
    {
        if (!isset($this->commandOptions[$commandIdentifier]['addSteps'][$stepIdentifier])) {
            $this->commandOptions[$commandIdentifier]['addSteps'][$stepIdentifier] = $stepIdentifier;
        }
    }

    /**
     * @param string $commandIdentifier
     * @param string $stepIdentifier
     * @internal
     */
    public function removeBootingStepForCommand(string $commandIdentifier, string $stepIdentifier)
    {
        if (!isset($this->commandOptions[$commandIdentifier]['removeSteps'])) {
            $this->commandOptions[$commandIdentifier]['removeSteps'][$stepIdentifier] = $stepIdentifier;
        }
    }

    /**
     * @param string $commandIdentifier
     * @throws \Exception
     * @internal
     */
    public function runSequenceForCommand(string $commandIdentifier)
    {
        $sequence = $this->buildSequenceForCommand($commandIdentifier);
        try {
            $sequence->invoke($this->bootstrap);
        } catch (StepFailedException $e) {
            $failedStep = $e->getFailedStep();
            if ($this->isLowLevelStep($failedStep)) {
                // This seems to be a severe error, so we directly throw to expose it
                throw $e->getPrevious();
            }

            $this->error = $e;
        }
    }

    /**
     * @return StepFailedException|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param string $commandIdentifier
     * @return bool
     * @internal
     */
    public function isInternalCommand(string $commandIdentifier): bool
    {
        return in_array($commandIdentifier, ['list', 'help'], true);
    }

    private function isLowLevelStep(Step $step): bool
    {
        $runLevelForStep = $this->executedSteps[$step->getIdentifier()];

        return in_array($runLevelForStep, [self::LEVEL_ESSENTIAL, self::LEVEL_COMPILE], true);
    }

    /**
     * @param string $runLevel
     * @internal
     * @throws InvalidArgumentException
     * @throws StepFailedException
     */
    public function runSequence(string $runLevel)
    {
        $this->buildSequence($runLevel)->invoke($this->bootstrap);
    }

    /**
     * Check if we have all mandatory files to assume we have a fully configured / installed TYPO3
     *
     * @return string
     */
    public function getMaximumAvailableRunLevel(): string
    {
        if (!file_exists(PATH_site . 'typo3conf/PackageStates.php') || !file_exists(PATH_site . 'typo3conf/LocalConfiguration.php')) {
            return self::LEVEL_COMPILE;
        }

        return $this->error ? self::LEVEL_COMPILE : self::LEVEL_FULL;
    }

    /**
     * @param string $commandIdentifier
     * @return bool
     */
    public function isCommandAvailable($commandIdentifier): bool
    {
        return $this->getMaximumAvailableRunLevel() === self::LEVEL_FULL
            || $this->getRunLevelForCommand($commandIdentifier) === self::LEVEL_COMPILE;
    }

    /**
     * @param string $commandIdentifier
     * @throws InvalidArgumentException
     * @return Sequence
     */
    private function buildSequenceForCommand(string $commandIdentifier): Sequence
    {
        $sequence = $this->buildSequence($this->getRunLevelForCommand($commandIdentifier));
        $this->addStepsForCommand($sequence, $commandIdentifier);
        $this->removeStepsForCommand($sequence, $commandIdentifier);

        return $sequence;
    }

    /**
     * Builds the sequence for the given run level
     *
     * @param string $runLevel
     * @throws InvalidArgumentException
     * @return Sequence
     */
    private function buildSequence(string $runLevel): Sequence
    {
        if (is_callable([$this, $runLevel])) {
            return $this->{$runLevel}($runLevel);
        }
        throw new InvalidArgumentException('Invalid run level "' . $runLevel . '"', 1402075492);
    }

    /**
     * Essential steps for a minimal usable system
     *
     * @param string $identifier
     * @return Sequence
     */
    private function buildEssentialSequence(string $identifier): Sequence
    {
        $sequence = new Sequence($identifier);
        $this->addStep($sequence, 'helhum.typo3console:coreconfiguration');
        $this->addStep($sequence, 'helhum.typo3console:providecleanclassimplementations');
        $this->addStep($sequence, 'helhum.typo3console:disabledcaching');
        $this->addStep($sequence, 'helhum.typo3console:errorhandling');

        return $sequence;
    }

    /**
     * System with complete configuration, but no database
     *
     * @param string $identifier
     * @return Sequence
     */
    private function buildBasicRuntimeSequence(string $identifier = self::LEVEL_MINIMAL): Sequence
    {
        $sequence = $this->buildEssentialSequence($identifier);

        $this->addStep($sequence, 'helhum.typo3console:extensionconfiguration');

        return $sequence;
    }

    /**
     * Fully capable system with database, persistence configuration (TCA) and authentication available
     *
     * @return Sequence
     */
    private function buildExtendedRuntimeSequence(): Sequence
    {
        $sequence = $this->buildBasicRuntimeSequence(self::LEVEL_FULL);

        $this->addStep($sequence, 'helhum.typo3console:caching');
        // @deprecated helhum.typo3console:database can be removed when TYPO3 8 support is removed
        $this->addStep($sequence, 'helhum.typo3console:database');
        $this->addStep($sequence, 'helhum.typo3console:persistence');
        $this->addStep($sequence, 'helhum.typo3console:authentication');

        return $sequence;
    }

    /**
     * @param Sequence $sequence
     * @param string $stepIdentifier
     * @throws InvalidArgumentException
     */
    private function addStep(Sequence $sequence, string $stepIdentifier)
    {
        if (isset($this->executedSteps[$stepIdentifier])) {
            $sequence->addStep(new Step($stepIdentifier, function () {
                // Don't do anything again, step has been executed already
            }));

            return;
        }

        switch ($stepIdentifier) {
            // Part of essential sequence
            case 'helhum.typo3console:coreconfiguration':
                $this->executedSteps[$stepIdentifier] = self::LEVEL_ESSENTIAL;
                $sequence->addStep(new Step($stepIdentifier, [Scripts::class, 'initializeConfigurationManagement']));
                break;
            case 'helhum.typo3console:providecleanclassimplementations':
                $this->executedSteps[$stepIdentifier] = self::LEVEL_ESSENTIAL;
                $sequence->addStep(new Step($stepIdentifier, [Scripts::class, 'provideCleanClassImplementations']), 'helhum.typo3console:coreconfiguration');
                break;
            case 'helhum.typo3console:disabledcaching':
                $this->executedSteps[$stepIdentifier] = self::LEVEL_ESSENTIAL;
                $sequence->addStep(new Step($stepIdentifier, [Scripts::class, 'initializeDisabledCaching']), 'helhum.typo3console:coreconfiguration');
                break;
            case 'helhum.typo3console:errorhandling':
                $this->executedSteps[$stepIdentifier] = self::LEVEL_ESSENTIAL;
                $sequence->addStep(new Step($stepIdentifier, [Scripts::class, 'initializeErrorHandling']));
                break;

            // Part of basic runtime
            case 'helhum.typo3console:extensionconfiguration':
                $this->executedSteps[$stepIdentifier] = self::LEVEL_MINIMAL;
                $sequence->addStep(new Step($stepIdentifier, [Scripts::class, 'initializeExtensionConfiguration']));
                break;

            // Part of full runtime
            case 'helhum.typo3console:caching':
                $this->executedSteps[$stepIdentifier] = self::LEVEL_FULL;
                unset($this->executedSteps['helhum.typo3console:disabledcaching']);
                $sequence->removeStep('helhum.typo3console:disabledcaching');
                $sequence->addStep(new Step($stepIdentifier, [Scripts::class, 'initializeCaching']), 'helhum.typo3console:coreconfiguration');
                break;
            case 'helhum.typo3console:database':
                // @deprecated can be removed if TYPO3 8 support is removed
                $this->executedSteps[$stepIdentifier] = self::LEVEL_FULL;
                $sequence->addStep(new Step($stepIdentifier, [CompatibilityScripts::class, 'initializeDatabaseConnection']), 'helhum.typo3console:errorhandling');
                break;
            case 'helhum.typo3console:persistence':
                $this->executedSteps[$stepIdentifier] = self::LEVEL_FULL;
                $sequence->addStep(new Step($stepIdentifier, [Scripts::class, 'initializePersistence']), 'helhum.typo3console:extensionconfiguration');
                break;
            case 'helhum.typo3console:authentication':
                $this->executedSteps[$stepIdentifier] = self::LEVEL_FULL;
                $sequence->addStep(new Step($stepIdentifier, [Scripts::class, 'initializeAuthenticatedOperations']), 'helhum.typo3console:extensionconfiguration');
                break;

            default:
                throw new InvalidArgumentException('ERROR: cannot find step for identifier "' . $stepIdentifier . '"', 1402075819);
        }
    }

    /**
     * @param string $commandIdentifier
     * @return string
     * @internal
     */
    public function getRunLevelForCommand(string $commandIdentifier): string
    {
        if ($this->isInternalCommand($commandIdentifier)) {
            return $this->getMaximumAvailableRunLevel() === self::LEVEL_COMPILE ? self::LEVEL_COMPILE : self::LEVEL_MINIMAL;
        }
        $options = $this->getOptionsForCommand($commandIdentifier);

        return $options['runLevel'] ?? self::LEVEL_FULL;
    }

    /**
     * @param Sequence $sequence
     * @param string $commandIdentifier
     * @internal
     */
    private function addStepsForCommand(Sequence $sequence, string $commandIdentifier)
    {
        $options = $this->getOptionsForCommand($commandIdentifier);
        if (isset($options['addSteps'])) {
            foreach ($options['addSteps'] as $stepIdentifier) {
                $this->addStep($sequence, $stepIdentifier);
            }
        }
    }

    /**
     * @param Sequence $sequence
     * @param string $commandIdentifier
     * @internal
     */
    private function removeStepsForCommand(Sequence $sequence, string $commandIdentifier)
    {
        $options = $this->getOptionsForCommand($commandIdentifier);
        if (isset($options['removeSteps'])) {
            foreach ($options['removeSteps'] as $stepIdentifier) {
                $sequence->removeStep($stepIdentifier);
            }
        }
    }

    /**
     * @param string $commandIdentifier
     * @return array|null
     */
    private function getOptionsForCommand(string $commandIdentifier)
    {
        $commandIdentifierPrefix = $commandIdentifier;
        $position = strrpos($commandIdentifier, ':');
        if ($position !== false) {
            $commandIdentifierPrefix = substr($commandIdentifier, 0, $position);
        }

        if (isset($this->commandOptions[$commandIdentifier])) {
            return $this->commandOptions[$commandIdentifier];
        }

        $lookupKey = $commandIdentifierPrefix . ':*';
        if (isset($this->commandOptions[$lookupKey])) {
            return $this->commandOptions[$lookupKey];
        }

        return null;
    }
}
