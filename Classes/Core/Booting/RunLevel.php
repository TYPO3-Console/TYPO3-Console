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
	 * @param $runLevel
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



	/**
	 * Essential steps for a minimal usable system
	 *
	 * @param string $identifier
	 * @return Sequence
	 */
	protected function buildEssentialSequence($identifier) {
		$sequence = new Sequence($identifier);

		$this->addStep($sequence, 'helhum.typo3console:coreconfiguration');
		$this->addStep($sequence, 'helhum.typo3console:caching');
		$this->addStep($sequence, 'helhum.typo3console:errorhandling');
		$this->addStep($sequence, 'helhum.typo3console:classloadercache');
		// Only after this point we have a fully functional class loader

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

		$sequence->addStep(new Step('replaceArgumentObject', function() {
			// TODO: find a better place for that
			require PATH_site . 'typo3/sysext/extbase/ext_localconf.php';
			/** @var $extbaseObjectContainer \TYPO3\CMS\Extbase\Object\Container\Container */
			$extbaseObjectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\Container\\Container');
			$extbaseObjectContainer->registerImplementation('TYPO3\CMS\Extbase\Mvc\Controller\Argument', 'Helhum\Typo3Console\Mvc\Controller\Argument');
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\CMS\Extbase\Mvc\Controller\Argument']['className'] = 'Helhum\Typo3Console\Mvc\Controller\Argument';
			class_alias('Helhum\Typo3Console\Mvc\Controller\Argument', 'TYPO3\CMS\Extbase\Mvc\Controller\Argument');
		}), 'helhum.typo3console:errorhandling');

		return $sequence;
	}

	/**
	 * System with complete configuration, but no database
	 *
	 * @param string $identifier
	 * @return Sequence
	 */
	protected function buildBasicRuntimeSequence($identifier = self::LEVEL_MINIMAL) {
		$sequence = $this->buildEssentialSequence($identifier);

		$this->addStep($sequence, 'helhum.typo3console:extensionconfiguration');
		// TODO: database is (only) required for the framework
		// * configuration manager needs it
		// * several caches have database backends
		$this->addStep($sequence, 'helhum.typo3console:database');

		return $sequence;
	}

	/**
	 * Fully capable system with database, persistence configuration (TCA) and authentication available
	 *
	 * @param string $identifier
	 * @return Sequence
	 */
	protected function buildExtendedRuntimeSequence($identifier = self::LEVEL_FULL) {
		$sequence = $this->buildBasicRuntimeSequence($identifier);

		$this->addStep($sequence, 'helhum.typo3console:persistence');
		$this->addStep($sequence, 'helhum.typo3console:authentication');

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
		return $sequence;
	}

	/**
	 * @param Sequence $sequence
	 * @param string $stepIdentifier
	 */
	protected function addStep($sequence, $stepIdentifier) {
		switch ($stepIdentifier) {
			// Part of essential sequence
			case 'helhum.typo3console:coreconfiguration':
				$sequence->addStep(new Step('helhum.typo3console:coreconfiguration', array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeConfigurationManagement')));
				break;
			case 'helhum.typo3console:caching':
				$sequence->addStep(new Step('helhum.typo3console:caching', array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeCachingFramework')));
				break;
			case 'helhum.typo3console:errorhandling':
				$sequence->addStep(new Step('helhum.typo3console:errorhandling', array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeErrorHandling')));
				break;
			case 'helhum.typo3console:classloadercache':
				$sequence->addStep(new Step('helhum.typo3console:classloadercache', array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeClassLoaderCaches')));
				break;

			// Part of compiletime sequence
			case 'helhum.typo3console:disabledcaches':
				$sequence->addStep(new Step('helhum.typo3console:disabledcaches', array('Helhum\Typo3Console\Core\Booting\Scripts', 'disableObjectCaches')), 'helhum.typo3console:coreconfiguration');
				break;

			// Part of basic runtime
			case 'helhum.typo3console:extensionconfiguration':
				$sequence->addStep(new Step('helhum.typo3console:extensionconfiguration', array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeExtensionConfiguration')), 'helhum.typo3console:classloadercache');
				break;

			// Part of full runtime
			case 'helhum.typo3console:database':
				$sequence->addStep(new Step('helhum.typo3console:database', array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeDatabaseConnection')), 'helhum.typo3console:errorhandling');
				break;
			case 'helhum.typo3console:persistence':
				$sequence->addStep(new Step('helhum.typo3console:persistence', array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializePersistence')), 'helhum.typo3console:extensionconfiguration');
				break;
			case 'helhum.typo3console:authentication':
				$sequence->addStep(new Step('helhum.typo3console:authentication', array('Helhum\Typo3Console\Core\Booting\Scripts', 'initializeAuthenticatedOperations')), 'helhum.typo3console:extensionconfiguration');
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
}