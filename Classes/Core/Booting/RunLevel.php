<?php
namespace Helhum\Typo3Console\Core\Booting;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class RunLevel
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
    protected $commandOptions = array();

    /**
     * @var array
     */
    protected $executedSequences = array();

    /**
     * @param string $commandIdentifier
     * @param string $runlevel
     * @api
     */
    public function setRunLevelForCommand($commandIdentifier, $runlevel)
    {
        if (!isset($this->commandOptions[$commandIdentifier]['runLevel'])) {
            $this->commandOptions[$commandIdentifier]['runLevel'] = $runlevel;
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
     * @return Sequence
     * @throws \InvalidArgumentException
     * @internal
     */
    public function buildSequence($runLevel)
    {
        if (is_callable(array($this, $runLevel))) {
            return $this->{$runLevel}($runLevel);
        } else {
            throw new \InvalidArgumentException('Invalid run level "' . $runLevel . '"', 1402075492);
        }
    }

    /**
     * Returns a sequence that only contains steps that have not been executed yet
     *
     * @param string $requestedRunLevel
     * @return Sequence
     */
    public function buildDifferentialSequenceUpToLevel($requestedRunLevel)
    {
        $executionOrder = array(self::LEVEL_ESSENTIAL, self::LEVEL_MINIMAL, self::LEVEL_FULL);
        $sequence = new Sequence($requestedRunLevel);
        foreach ($executionOrder as $runLevel) {
            $sequence = $this->{$runLevel}($runLevel, $sequence);
            if ($runLevel === $requestedRunLevel) {
                break;
            }
        }

        return $sequence;
    }

    /**
     * Essential steps for a minimal usable system
     *
     * @param string $identifier
     * @param Sequence $parentSequence
     * @return Sequence
     */
    protected function buildEssentialSequence($identifier, $parentSequence = null)
    {
        $sequence = $parentSequence ?: new Sequence($identifier);

        $this->addStep($sequence, 'helhum.typo3console:coreconfiguration', !empty($this->executedSequences[self::LEVEL_ESSENTIAL]));
        $this->addStep($sequence, 'helhum.typo3console:caching', !empty($this->executedSequences[self::LEVEL_ESSENTIAL]));
        $this->addStep($sequence, 'helhum.typo3console:errorhandling', !empty($this->executedSequences[self::LEVEL_ESSENTIAL]));
        $this->addStep($sequence, 'helhum.typo3console:classloadercache', !empty($this->executedSequences[self::LEVEL_ESSENTIAL]));

        $this->executedSequences[self::LEVEL_ESSENTIAL] = true;
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
        $sequence->removeStep('helhum.typo3console:classloadercache');

        $sequence->addStep(new Step('helhum.typo3console:loadextbaseconfiguration', function () {
            // TODO: hack alarm :) We remove this in order to prevent double inclusion of the ext_localconf.php
            // This should be fine although not very nice
            // We should change that to include all ext_localconf of required exts in configuration step and reset this array key there then
            // OK, this does not work when there is a cached file... of course, but in compile time we do not have caches
            unset($GLOBALS['TYPO3_LOADED_EXT']['extbase']['ext_localconf.php']);
            require PATH_site . 'typo3/sysext/extbase/ext_localconf.php';
        }));
        $sequence->addStep(new Step('helhum.typo3console:providecleanclassimplementations', array('Helhum\Typo3Console\Core\Booting\Scripts', 'provideCleanClassImplementations')));

        $this->executedSequences[self::LEVEL_COMPILE] = true;
        return $sequence;
    }

    /**
     * System with complete configuration, but no database
     *
     * @param string $identifier
     * @param Sequence $parentSequence
     * @return Sequence
     */
    protected function buildBasicRuntimeSequence($identifier = self::LEVEL_MINIMAL, $parentSequence = null)
    {
        $sequence = $parentSequence ?: $this->buildEssentialSequence($identifier);

        $this->addStep($sequence, 'helhum.typo3console:extensionconfiguration', !empty($this->executedSequences[self::LEVEL_MINIMAL]));
        if (empty($this->executedSequences[self::LEVEL_COMPILE])) {
            // Only execute if not already executed in compile time
            $sequence->addStep(new Step('helhum.typo3console:providecleanclassimplementations', array('Helhum\Typo3Console\Core\Booting\Scripts', 'provideCleanClassImplementations')), 'helhum.typo3console:extensionconfiguration');
        }

        $this->executedSequences[self::LEVEL_MINIMAL] = true;
        return $sequence;
    }

    /**
     * Fully capable system with database, persistence configuration (TCA) and authentication available
     *
     * @param string $identifier
     * @param Sequence $parentSequence
     * @return Sequence
     */
    protected function buildExtendedRuntimeSequence($identifier = self::LEVEL_FULL, $parentSequence = null)
    {
        $sequence = $parentSequence ?: $this->buildBasicRuntimeSequence($identifier);

        $this->addStep($sequence, 'helhum.typo3console:database', !empty($this->executedSequences[self::LEVEL_FULL]));
        // Fix core caches that were disabled beforehand
        $this->addStep($sequence, 'helhum.typo3console:enablecorecaches');
        $this->addStep($sequence, 'helhum.typo3console:persistence', !empty($this->executedSequences[self::LEVEL_FULL]));
        $this->addStep($sequence, 'helhum.typo3console:authentication', !empty($this->executedSequences[self::LEVEL_FULL]));

        $this->executedSequences[self::LEVEL_FULL] = true;
        return $sequence;
    }

    /**
     * @param Sequence $sequence
     * @param string $stepIdentifier
     * @param bool $isDummyStep
     */
    protected function addStep($sequence, $stepIdentifier, $isDummyStep = false)
    {
        switch ($stepIdentifier) {

            // Part of essential sequence
            case 'helhum.typo3console:coreconfiguration':
                $action = $isDummyStep ? function () {} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeConfigurationManagement');
                $sequence->addStep(new Step('helhum.typo3console:coreconfiguration', $action));
                break;
            case 'helhum.typo3console:caching':
                $action = $isDummyStep ? function () {} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeCachingFramework');
                $sequence->addStep(new Step('helhum.typo3console:caching', $action));
                break;
            case 'helhum.typo3console:errorhandling':
                $action = $isDummyStep ? function () {} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeErrorHandling');
                $sequence->addStep(new Step('helhum.typo3console:errorhandling', $action));
                break;
            case 'helhum.typo3console:classloadercache':
                $action = $isDummyStep ? function () {} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeClassLoaderCaches');
                $sequence->addStep(new Step('helhum.typo3console:classloadercache', $action));
                break;

            // Part of compiletime sequence
            case 'helhum.typo3console:disablecorecaches':
                $sequence->addStep(new Step('helhum.typo3console:disablecorecaches', array('Helhum\Typo3Console\Core\Booting\Scripts', 'disableCoreCaches')), 'helhum.typo3console:coreconfiguration');
                break;

            // Part of basic runtime
            case 'helhum.typo3console:extensionconfiguration':
                $action = $isDummyStep ? function () {} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeExtensionConfiguration');
                $sequence->addStep(new Step('helhum.typo3console:extensionconfiguration', $action), 'helhum.typo3console:classloadercache');
                break;
            case 'helhum.typo3console:enablecorecaches':
                $action = $isDummyStep ? function () {} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'reEnableOriginalCoreCaches');
                $sequence->addStep(new Step('helhum.typo3console:enablecorecaches', $action), 'helhum.typo3console:database');
                break;

            // Part of full runtime
            case 'helhum.typo3console:database':
                $action = $isDummyStep ? function () {} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeDatabaseConnection');
                $sequence->addStep(new Step('helhum.typo3console:database', $action), 'helhum.typo3console:errorhandling');
                break;
            case 'helhum.typo3console:persistence':
                $action = $isDummyStep ? function () {} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializePersistence');
                $sequence->addStep(new Step('helhum.typo3console:persistence', $action), 'helhum.typo3console:extensionconfiguration');
                break;
            case 'helhum.typo3console:authentication':
                $action = $isDummyStep ? function () {} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeAuthenticatedOperations');
                $sequence->addStep(new Step('helhum.typo3console:authentication', $action), 'helhum.typo3console:extensionconfiguration');
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

    public function isCommandAvailable($commandIdentifier)
    {
        $expectedRunLevel = $this->getRunlevelForCommand($commandIdentifier);
        $availableRunlevel = $this->getMaximumAvailableRunLevel();
        $isAvailable = true;
        if ($availableRunlevel === self::LEVEL_COMPILE) {
            if (in_array($expectedRunLevel, array(self::LEVEL_FULL, self::LEVEL_MINIMAL))) {
                $isAvailable = false;
            }
        }

        return $isAvailable;
    }

    /**
     * @param Sequence $sequence
     * @param string $commandIdentifier
     * @return string
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
     * @return string
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
            } elseif ($controllerName === $currentCommandControllerName && $commandName === '*') {
                return $this->commandOptions[$fullControllerIdentifier];
            }
        }

        return null;
    }
}
