<?php
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

class RunLevel
{
    const LEVEL_ESSENTIAL = 'buildEssentialSequence';
    const LEVEL_COMPILE = 'buildCompiletimeSequence';
    const LEVEL_MINIMAL = 'buildBasicRuntimeSequence';
    const LEVEL_FULL = 'buildExtendedRuntimeSequence';

    /**
     * @var array
     */
    protected $commandOptions = [];

    /**
     * @var array
     */
    protected $executedSteps = [];

    /**
     * @param string $commandIdentifier
     * @param string $runLevel
     * @api
     */
    public function setRunLevelForCommand($commandIdentifier, $runLevel)
    {
        if (!isset($this->commandOptions[$commandIdentifier]['runLevel'])) {
            $this->commandOptions[$commandIdentifier]['runLevel'] = $runLevel;
        }
    }

    /**
     * @param $commandIdentifier
     * @param string $stepIdentifier
     * @internal
     */
    public function addBootingStepForCommand($commandIdentifier, $stepIdentifier)
    {
        if (!isset($this->commandOptions[$commandIdentifier]['addSteps'][$stepIdentifier])) {
            $this->commandOptions[$commandIdentifier]['addSteps'][$stepIdentifier] = $stepIdentifier;
        }
    }

    /**
     * @param $commandIdentifier
     * @param string $stepIdentifier
     * @internal
     */
    public function removeBootingStepForCommand($commandIdentifier, $stepIdentifier)
    {
        if (!isset($this->commandOptions[$commandIdentifier]['removeSteps'])) {
            $this->commandOptions[$commandIdentifier]['removeSteps'][$stepIdentifier] = $stepIdentifier;
        }
    }

    /**
     * @param string $commandIdentifier
     * @return Sequence
     * @internal
     */
    public function buildSequenceForCommand($commandIdentifier)
    {
        $sequence = $this->buildSequence($this->getRunlevelForCommand($commandIdentifier));
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
     * @internal
     */
    public function buildSequence($runLevel)
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
    protected function buildEssentialSequence($identifier)
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
    protected function buildCompiletimeSequence()
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
    protected function buildBasicRuntimeSequence($identifier = self::LEVEL_MINIMAL)
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
    protected function buildExtendedRuntimeSequence()
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
    protected function addStep($sequence, $stepIdentifier)
    {
        if (!empty($this->executedSteps[$stepIdentifier])) {
            $sequence->addStep(new Step($stepIdentifier, function () {
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
                $sequence->addStep(new Step('helhum.typo3console:enablecorecaches', [Scripts::class, 'reEnableOriginalCoreCaches']), 'helhum.typo3console:database');
                break;
            // @deprecated can be removed if TYPO3 8 support is removed
            case 'helhum.typo3console:database':
                $sequence->addStep(new Step('helhum.typo3console:database', [Scripts::class, 'initializeDatabaseConnection']), 'helhum.typo3console:errorhandling');
                break;
            case 'helhum.typo3console:authentication':
                $sequence->addStep(new Step('helhum.typo3console:authentication', [Scripts::class, 'initializeAuthenticatedOperations']), 'helhum.typo3console:extensionconfiguration');
                break;

            default:
                throw new \InvalidArgumentException('ERROR: cannot find step for identifier "' . $stepIdentifier . '"', 1402075819);
        }
    }

    // COMMAND RELATED

    /**
     * @param string $commandIdentifier
     * @return string
     * @internal
     */
    public function getRunlevelForCommand($commandIdentifier)
    {
        if ($commandIdentifier === '' || $commandIdentifier === 'help') {
            return $this->getMaximumAvailableRunLevel();
        }
        $options = $this->getOptionsForCommand($commandIdentifier);
        return isset($options['runLevel']) ? $options['runLevel'] : self::LEVEL_FULL;
    }

    /**
     * Check if we have all mandatory files to assume we have a fully configured / installed TYPO3
     *
     * @return bool
     */
    public function getMaximumAvailableRunLevel()
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
    public function isCommandAvailable($commandIdentifier)
    {
        $expectedRunLevel = $this->getRunlevelForCommand($commandIdentifier);
        $availableRunlevel = $this->getMaximumAvailableRunLevel();
        $isAvailable = true;
        if ($availableRunlevel === self::LEVEL_COMPILE) {
            if (in_array($expectedRunLevel, [self::LEVEL_FULL, self::LEVEL_MINIMAL], true)) {
                $isAvailable = false;
            }
        }

        return $isAvailable;
    }

    /**
     * @param Sequence $sequence
     * @param string $commandIdentifier
     * @internal
     */
    protected function addStepsForCommand($sequence, $commandIdentifier)
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
    protected function removeStepsForCommand($sequence, $commandIdentifier)
    {
        $options = $this->getOptionsForCommand($commandIdentifier);
        if (isset($options['removeSteps'])) {
            foreach ($options['removeSteps'] as $stepIdentifier) {
                $sequence->removeStep($stepIdentifier);
            }
        }
    }

    /**
     * @param $commandIdentifier
     * @return mixed
     */
    protected function getOptionsForCommand($commandIdentifier)
    {
        $commandIdentifierParts = explode(':', $commandIdentifier);
        if (count($commandIdentifierParts) < 2 || count($commandIdentifierParts) > 3) {
            return null;
        }
        if (isset($this->commandOptions[$commandIdentifier])) {
            return $this->commandOptions[$commandIdentifier];
        }

        if (count($commandIdentifierParts) === 3) {
            $currentCommandPackageName = $commandIdentifierParts[0];
            $currentCommandControllerName = $commandIdentifierParts[1];
            $currentCommandName = $commandIdentifierParts[2];
        } else {
            $currentCommandControllerName = $commandIdentifierParts[0];
            $currentCommandName = $commandIdentifierParts[1];
        }

        foreach ($this->commandOptions as $fullControllerIdentifier => $commandRegistry) {
            list($packageKey, $controllerName, $commandName) = explode(':', $fullControllerIdentifier);
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
