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

use TYPO3\CMS\Core\Core\Bootstrap;

class RunLevel
{
    const LEVEL_ESSENTIAL = 'buildEssentialSequence';
    const LEVEL_COMPILE = 'buildCompiletimeSequence';
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
     * @throws \InvalidArgumentException
     * @internal
     */
    public function runSequenceForCommand(string $commandIdentifier)
    {
        $this->buildSequenceForCommand($commandIdentifier)->invoke($this->bootstrap);
    }

    /**
     * @param string $runLevel
     * @internal
     * @throws \InvalidArgumentException
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

        return self::LEVEL_FULL;
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
     * @throws \InvalidArgumentException
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
     * @throws \InvalidArgumentException
     * @return Sequence
     */
    private function buildSequence(string $runLevel): Sequence
    {
        if (is_callable([$this, $runLevel])) {
            return $this->{$runLevel}($runLevel);
        }
        throw new \InvalidArgumentException('Invalid run level "' . $runLevel . '"', 1402075492);
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
        $this->addStep($sequence, 'helhum.typo3console:caching');
        $this->addStep($sequence, 'helhum.typo3console:errorhandling');

        return $sequence;
    }

    /**
     * Minimal usable system with most core caches disabled
     *
     * @return Sequence
     */
    private function buildCompiletimeSequence(): Sequence
    {
        $sequence = $this->buildEssentialSequence(self::LEVEL_COMPILE);

        $this->addStep($sequence, 'helhum.typo3console:disablecorecaches');

        $sequence->addStep(new Step('helhum.typo3console:loadextbaseconfiguration', function () {
            // TODO: hack alarm :) We remove this in order to prevent double inclusion of the ext_localconf.php
            // This should be fine although not very nice
            // We should change that to include all ext_localconf of required exts in configuration step and reset this array key there then
            // OK, this does not work when there is a cached file... of course, but in compile time we do not have caches
            unset($GLOBALS['TYPO3_LOADED_EXT']['extbase']['ext_localconf.php']);
            require PATH_site . 'typo3/sysext/extbase/ext_localconf.php';
        }));

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

        // @deprecated can be removed if TYPO3 8 support is removed
        $this->addStep($sequence, 'helhum.typo3console:database');
        // Fix core caches that were disabled beforehand
        $this->addStep($sequence, 'helhum.typo3console:enablecorecaches');
        $this->addStep($sequence, 'helhum.typo3console:authentication');

        return $sequence;
    }

    /**
     * @param Sequence $sequence
     * @param string $stepIdentifier
     */
    private function addStep(Sequence $sequence, string $stepIdentifier)
    {
        if (!empty($this->executedSteps[$stepIdentifier])) {
            $sequence->addStep(new Step($stepIdentifier, function () {
                // Don't do anything again, step has been executed already
            }));
            return;
        }
        $this->executedSteps[$stepIdentifier] = true;

        switch ($stepIdentifier) {
            // Part of essential sequence
            case 'helhum.typo3console:coreconfiguration':
                $sequence->addStep(new Step('helhum.typo3console:coreconfiguration', [Scripts::class, 'initializeConfigurationManagement']));
                break;
            case 'helhum.typo3console:providecleanclassimplementations':
                $sequence->addStep(new Step('helhum.typo3console:providecleanclassimplementations', [Scripts::class, 'provideCleanClassImplementations']), 'helhum.typo3console:coreconfiguration');
                break;
            case 'helhum.typo3console:caching':
                $sequence->addStep(new Step('helhum.typo3console:caching', [Scripts::class, 'initializeCachingFramework']));
                break;
            case 'helhum.typo3console:errorhandling':
                $sequence->addStep(new Step('helhum.typo3console:errorhandling', [Scripts::class, 'initializeErrorHandling']));
                break;

            // Part of compiletime sequence
            case 'helhum.typo3console:disablecorecaches':
                $sequence->addStep(new Step('helhum.typo3console:disablecorecaches', [Scripts::class, 'disableCoreCaches']), 'helhum.typo3console:coreconfiguration');
                break;

            // Part of basic runtime
            case 'helhum.typo3console:extensionconfiguration':
                $sequence->addStep(new Step('helhum.typo3console:extensionconfiguration', [Scripts::class, 'initializeExtensionConfiguration']));
                break;

            // Part of full runtime
            case 'helhum.typo3console:enablecorecaches':
                $sequence->addStep(new Step('helhum.typo3console:enablecorecaches', [Scripts::class, 'reEnableOriginalCoreCaches']), 'helhum.typo3console:extensionconfiguration');
                break;
            // @deprecated can be removed if TYPO3 8 support is removed
            case 'helhum.typo3console:database':
                $sequence->addStep(new Step('helhum.typo3console:database', [CompatibilityScripts::class, 'initializeDatabaseConnection']), 'helhum.typo3console:errorhandling');
                break;
            case 'helhum.typo3console:authentication':
                $sequence->addStep(new Step('helhum.typo3console:authentication', [Scripts::class, 'initializeAuthenticatedOperations']), 'helhum.typo3console:extensionconfiguration');
                break;

            default:
                throw new \InvalidArgumentException('ERROR: cannot find step for identifier "' . $stepIdentifier . '"', 1402075819);
        }
    }

    /**
     * @param string $commandIdentifier
     * @return string
     * @internal
     */
    private function getRunLevelForCommand(string $commandIdentifier): string
    {
        if ($commandIdentifier === '' || $commandIdentifier === 'help') {
            return $this->getMaximumAvailableRunLevel();
        }
        $options = $this->getOptionsForCommand($commandIdentifier);
        return isset($options['runLevel']) ? $options['runLevel'] : self::LEVEL_FULL;
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
        $commandIdentifierParts = explode(':', $commandIdentifier);
        if (count($commandIdentifierParts) < 2 || count($commandIdentifierParts) > 3) {
            return null;
        }
        if (isset($this->commandOptions[$commandIdentifier])) {
            return $this->commandOptions[$commandIdentifier];
        }

        if (count($commandIdentifierParts) === 3) {
            $currentCommandControllerName = $commandIdentifierParts[1];
            $currentCommandName = $commandIdentifierParts[2];
        } else {
            $currentCommandControllerName = $commandIdentifierParts[0];
            $currentCommandName = $commandIdentifierParts[1];
        }

        foreach ($this->commandOptions as $fullControllerIdentifier => $commandRegistry) {
            list(, $controllerName, $commandName) = explode(':', $fullControllerIdentifier);
            if ($controllerName === $currentCommandControllerName && $commandName === $currentCommandName) {
                return $this->commandOptions[$fullControllerIdentifier];
            }
            if ($controllerName === $currentCommandControllerName && $commandName === '*') {
                return $this->commandOptions[$fullControllerIdentifier];
            }
        }

        return null;
    }
}
