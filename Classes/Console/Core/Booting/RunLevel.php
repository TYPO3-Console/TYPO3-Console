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

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Core\Bootstrap;

class RunLevel
{
    public const LEVEL_ESSENTIAL = 'buildEssentialSequence';
    public const LEVEL_COMPILE = 'buildEssentialSequence';
    public const LEVEL_MINIMAL = 'buildBasicRuntimeSequence';
    private const LEVEL_UNCACHED = 'buildExtendedUncachedRuntimeSequence';
    public const LEVEL_FULL = 'buildExtendedRuntimeSequence';

    /**
     * @var array
     */
    private $commandOptions = [];

    /**
     * @var array
     */
    private $executedSteps = [];

    /**
     * @var StepFailedException
     */
    private $error;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container, ?StepFailedException $lowLevelError)
    {
        $this->container = $container;
        $this->error = $lowLevelError;
    }

    /**
     * @param string $commandIdentifier
     * @param string $runLevel
     * @internal
     */
    public function setRunLevelForCommand(string $commandIdentifier, string $runLevel)
    {
        $this->commandOptions[$commandIdentifier]['runLevel'] = $runLevel;
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
            $sequence->invoke();
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
     * @param string|null $commandIdentifier
     * @return StepFailedException|null
     */
    public function getError(string $commandIdentifier = null): ?StepFailedException
    {
        if ($commandIdentifier !== null
            && $this->error instanceof ContainerBuildFailedException
            && $this->isLowLevelCommand($commandIdentifier)
        ) {
            return null;
        }

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
        $this->buildSequence($runLevel)->invoke();
    }

    /**
     * Check if we have all mandatory files to assume we have a fully configured / installed TYPO3
     *
     * @return string
     */
    public function getMaximumAvailableRunLevel(): string
    {
        if (!Bootstrap::checkIfEssentialConfigurationExists(new ConfigurationManager())) {
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
            || $this->isLowLevelCommand($commandIdentifier);
    }

    /**
     * @param string $commandIdentifier
     * @return bool
     */
    public function isLowLevelCommand($commandIdentifier): bool
    {
        $options = $this->getOptionsForCommand($commandIdentifier);
        $runLevel = $options['runLevel'] ?? self::LEVEL_FULL;

        return $runLevel === self::LEVEL_COMPILE;
    }

    /**
     * @param string $commandIdentifier
     * @throws InvalidArgumentException
     * @return Sequence
     */
    private function buildSequenceForCommand(string $commandIdentifier): Sequence
    {
        $sequence = $this->buildSequence($runLevel = $this->getRunLevelForCommand($commandIdentifier));
        $this->addStepsForCommand($sequence, $commandIdentifier);
        $this->removeStepsForCommand($sequence, $commandIdentifier);
        $this->container->get('boot.state')->runLevel = $runLevel;

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
        return new Sequence($identifier);
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
     * Fully capable system with database, persistence configuration (TCA) and authentication available,
     * but not activating core caches
     *
     * @param string $identifier
     * @return Sequence
     */
    private function buildExtendedUncachedRuntimeSequence(string $identifier = self::LEVEL_UNCACHED): Sequence
    {
        return $this->buildExtendedRuntimeSequence($identifier);
    }

    /**
     * Fully capable system with database, persistence configuration (TCA) and authentication available
     *
     * @param string $identifier
     * @return Sequence
     */
    private function buildExtendedRuntimeSequence(string $identifier = self::LEVEL_FULL): Sequence
    {
        $sequence = $this->buildBasicRuntimeSequence($identifier);

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
            // Part of basic runtime
            case 'helhum.typo3console:extensionconfiguration':
                $this->executedSteps[$stepIdentifier] = self::LEVEL_MINIMAL;
                $sequence->addStep(
                    new Step(
                        $stepIdentifier,
                        function () {
                            Scripts::initializeExtensionConfiguration($this->container);
                        }
                    )
                );
                break;

            // Part of full runtime
            case 'helhum.typo3console:persistence':
                $this->executedSteps[$stepIdentifier] = self::LEVEL_FULL;
                $sequence->addStep(
                    new Step(
                        $stepIdentifier,
                        function () {
                            Scripts::initializePersistence($this->container);
                        }
                    ),
                    'helhum.typo3console:extensionconfiguration'
                );
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
            return $this->getMaximumAvailableRunLevel() === self::LEVEL_COMPILE ? self::LEVEL_COMPILE : self::LEVEL_UNCACHED;
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
