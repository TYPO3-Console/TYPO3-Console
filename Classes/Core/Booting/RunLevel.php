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

use Helhum\Typo3Console\Core\ConsoleBootstrap;

/**
 * Class RunLevel
 */
class RunLevel {

	const LEVEL_ESSENTIAL = 'buildEssentialSequence';
	const LEVEL_COMPILE = 'buildCompiletimeSequence';
	const LEVEL_MINIMAL = 'buildBasicRuntimeSequence';
	const LEVEL_FULL = 'buildExtendedRuntimeSequence';
	const LEVEL_LEGACY = 'buildLegacySequence';

	/**
	 * Store sequences that have been executed
	 *
	 * @var array
	 */
	protected $executedSequences = array();

	/**
	 * @var array
	 */
	protected $commandOptions = array();

	/**
	 * @param string $commandIdentifier
	 * @param string $runlevel
	 * @api
	 */
	public function setRunLevelForCommand($commandIdentifier, $runlevel) {
		if (!isset($this->commandOptions[$commandIdentifier]['runLevel'])) {
			$this->commandOptions[$commandIdentifier]['runLevel'] = $runlevel;
		}
	}

	/**
	 * @param $commandIdentifier
	 * @param string $stepIdentifier
	 * @internal
	 */
	public function addBootingStepForCommand($commandIdentifier, $stepIdentifier) {
		if (!isset($this->commandOptions[$commandIdentifier]['addSteps'])) {
			$this->commandOptions[$commandIdentifier]['addSteps'][$stepIdentifier] = $stepIdentifier;
		}

	}

	/**
	 * @param $commandIdentifier
	 * @param string $stepIdentifier
	 * @internal
	 */
	public function removeBootingStepForCommand($commandIdentifier, $stepIdentifier) {
		if (!isset($this->commandOptions[$commandIdentifier]['removeSteps'])) {
			$this->commandOptions[$commandIdentifier]['removeSteps'][$stepIdentifier] = $stepIdentifier;
		}
	}

	/**
	 * @param string $commandIdentifier
	 * @return Sequence
	 * @internal
	 */
	public function buildSequenceForCommand($commandIdentifier) {
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
	 * @internal
	 */
	public function buildSequence($runLevel) {
		if (is_callable(array($this, $runLevel))) {
			return $this->{$runLevel}($runLevel);
		} else {
			echo 'Invalid run level "' . $runLevel . '"' . PHP_EOL;
			exit(1);
		}
	}

	/**
	 * Returns a sequence that only contains steps that have not been executed yet
	 *
	 * @param string $requestedRunLevel
	 * @return Sequence
	 */
	public function buildDifferentialSquenceUpToLevel($requestedRunLevel) {
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
	protected function buildEssentialSequence($identifier, $parentSequence = NULL) {
		$sequence = $parentSequence ?: new Sequence($identifier);

		$this->addStep($sequence, 'helhum.typo3console:coreconfiguration', $parentSequence ? TRUE : FALSE);
		$this->addStep($sequence, 'helhum.typo3console:caching', $parentSequence ? TRUE : FALSE);
		$this->addStep($sequence, 'helhum.typo3console:errorhandling', $parentSequence ? TRUE : FALSE);
		$this->addStep($sequence, 'helhum.typo3console:classloadercache', $parentSequence ? TRUE : FALSE);
		// Only after this point we have a fully functional class loader

		$this->executedSequences[self::LEVEL_ESSENTIAL] = TRUE;
		return $sequence;
	}

	/**
	 * Minimal usable system with most core caches disabled
	 *
	 * @return Sequence
	 */
	protected function buildCompiletimeSequence() {
		$sequence = $this->buildEssentialSequence(self::LEVEL_COMPILE);

		$this->addStep($sequence, 'helhum.typo3console:disabledcaches');
		$sequence->removeStep('helhum.typo3console:classloadercache');
		$sequence->addStep(new Step('helhum.typo3console:loadextbaseconfiguration', function() {
			// TODO: hack alarm :) We remove this in order to prevent double inclusion of the ext_localconf.php
			// This should be fine although not very nice
			// We should change that to include all ext_localconf of required exts in configuration step and reset this array key there then
			unset($GLOBALS['TYPO3_LOADED_EXT']['extbase']['ext_localconf.php']);
			require PATH_site . 'typo3/sysext/extbase/ext_localconf.php';
		}));
		$sequence->addStep(new Step('helhum.typo3console:providecleanclassimplementations', array('Helhum\Typo3Console\Core\Booting\Scripts', 'provideCleanClassImplementations')));

		$this->executedSequences[self::LEVEL_COMPILE] = TRUE;
		return $sequence;
	}

	/**
	 * System with complete configuration, but no database
	 *
	 * @param string $identifier
	 * @param Sequence $parentSequence
	 * @return Sequence
	 */
	protected function buildBasicRuntimeSequence($identifier = self::LEVEL_MINIMAL, $parentSequence = NULL) {
		$sequence = $parentSequence ?: $this->buildEssentialSequence($identifier);

		$this->addStep($sequence, 'helhum.typo3console:extensionconfiguration', $parentSequence ? TRUE : FALSE);
		// TODO: database is (only) required for the framework
		// * configuration manager needs it
		// * several caches have database backends
		$this->addStep($sequence, 'helhum.typo3console:database', $parentSequence ? TRUE : FALSE);

		$this->executedSequences[self::LEVEL_MINIMAL] = TRUE;
		return $sequence;
	}

	/**
	 * Fully capable system with database, persistence configuration (TCA) and authentication available
	 *
	 * @param string $identifier
	 * @param Sequence $parentSequence
	 * @return Sequence
	 */
	protected function buildExtendedRuntimeSequence($identifier = self::LEVEL_FULL, $parentSequence = NULL) {
		$sequence = $parentSequence ?: $this->buildBasicRuntimeSequence($identifier);

		$this->addStep($sequence, 'helhum.typo3console:persistence', $parentSequence ? TRUE : FALSE);
		$this->addStep($sequence, 'helhum.typo3console:authentication', $parentSequence ? TRUE : FALSE);

		$this->executedSequences[self::LEVEL_FULL] = TRUE;
		return $sequence;
	}

	/**
	 * Complete bootstrap in traditional order and with no possibility to inject steps
	 *
	 * @return Sequence
	 */
	protected function buildLegacySequence() {
		$sequence = new Sequence(self::LEVEL_LEGACY);
		$this->addStep($sequence, 'helhum.typo3console:runLegacyBootstrap');

		$this->executedSequences[self::LEVEL_LEGACY] = TRUE;
		return $sequence;
	}

	/**
	 * @param Sequence $sequence
	 * @param string $stepIdentifier
	 * @param bool $isDummyStep
	 */
	protected function addStep($sequence, $stepIdentifier, $isDummyStep = FALSE) {
		switch ($stepIdentifier) {
			// Part of essential sequence
			case 'helhum.typo3console:coreconfiguration':
				$action = $isDummyStep ? function(){} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeConfigurationManagement');
				$sequence->addStep(new Step('helhum.typo3console:coreconfiguration', $action));
				break;
			case 'helhum.typo3console:caching':
				$action = $isDummyStep ? function(){} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeCachingFramework');
				$sequence->addStep(new Step('helhum.typo3console:caching', $action));
				break;
			case 'helhum.typo3console:errorhandling':
				$action = $isDummyStep ? function(){} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeErrorHandling');
				$sequence->addStep(new Step('helhum.typo3console:errorhandling', $action));
				break;
			case 'helhum.typo3console:classloadercache':
				$action = $isDummyStep ? function(){} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeClassLoaderCaches');
				$sequence->addStep(new Step('helhum.typo3console:classloadercache', $action));
				break;

			// Part of compiletime sequence
			case 'helhum.typo3console:disabledcaches':
				$sequence->addStep(new Step('helhum.typo3console:disabledcaches', array('Helhum\Typo3Console\Core\Booting\Scripts', 'disableObjectCaches')), 'helhum.typo3console:coreconfiguration');
				break;

			// Part of basic runtime
			case 'helhum.typo3console:extensionconfiguration':
				$action = $isDummyStep ? function(){} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeExtensionConfiguration');
				$sequence->addStep(new Step('helhum.typo3console:extensionconfiguration', $action), 'helhum.typo3console:classloadercache');
				break;

			// Part of full runtime
			case 'helhum.typo3console:database':
				$action = $isDummyStep ? function(){} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeDatabaseConnection');
				$sequence->addStep(new Step('helhum.typo3console:database', $action), 'helhum.typo3console:errorhandling');
				break;
			case 'helhum.typo3console:persistence':
				$action = $isDummyStep ? function(){} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializePersistence');
				$sequence->addStep(new Step('helhum.typo3console:persistence', $action), 'helhum.typo3console:extensionconfiguration');
				break;
			case 'helhum.typo3console:authentication':
				$action = $isDummyStep ? function(){} : array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeAuthenticatedOperations');
				$sequence->addStep(new Step('helhum.typo3console:authentication', $action), 'helhum.typo3console:extensionconfiguration');
				break;

			// Legacy booting
			case 'helhum.typo3console:runLegacyBootstrap':
				$sequence->addStep(new Step('helhum.typo3console:runLegacyBootstrap', array('Helhum\Typo3Console\Core\Booting\Scripts', 'runLegacyBootstrap')));
				break;

			default:
				echo 'ERROR: cannot find step for identifier "' . $stepIdentifier . '"' . PHP_EOL;
				exit(1);
		}
	}

	// COMMAND RELATED

	/**
	 * @param string $commandIdentifier
	 * @return string
	 * @internal
	 */
	protected function getRunlevelForCommand($commandIdentifier) {
		$options = $this->getOptionsForCommand($commandIdentifier);
		return isset($options['runLevel']) ? $options['runLevel'] : self::LEVEL_LEGACY;
	}

	/**
	 * @param Sequence $sequence
	 * @param string $commandIdentifier
	 * @return string
	 * @internal
	 */
	protected function addStepsForCommand($sequence, $commandIdentifier) {
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
	protected function removeStepsForCommand($sequence, $commandIdentifier) {
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
	protected function getOptionsForCommand($commandIdentifier) {
		$commandIdentifierParts = explode(':', $commandIdentifier);
		if (count($commandIdentifierParts) < 2 || count($commandIdentifierParts) > 3) {
			return NULL;
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

		return NULL;
	}
}