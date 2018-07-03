<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Composer;

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

use Composer\Script\Event;
use Composer\Semver\Constraint\EmptyConstraint;
use Helhum\Typo3Console\Composer\InstallerScript\PopulateCommandConfiguration;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScripts\AutoloadConnector;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScripts\WebDirectory;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScriptsRegistration;
use TYPO3\CMS\Composer\Plugin\Core\ScriptDispatcher;

/**
 * Scripts executed on composer build time
 */
class InstallerScripts implements InstallerScriptsRegistration
{
    /**
     * Allows to register one or more script objects that implement this interface
     * This will be called in the Plugin right before the scripts are executed.
     *
     * @param Event $event
     * @param ScriptDispatcher $scriptDispatcher
     * @return void
     */
    public static function register(Event $event, ScriptDispatcher $scriptDispatcher)
    {
        $scriptDispatcher->addInstallerScript(new PopulateCommandConfiguration(), 70);
        if (!class_exists(\TYPO3\CMS\Core\Composer\InstallerScripts::class)
            && !class_exists(\Helhum\Typo3ComposerSetup\Composer\InstallerScripts::class)
            && $event->getComposer()->getRepositoryManager()->getLocalRepository()->findPackage('typo3/cms', new EmptyConstraint()) !== null
        ) {
            // @deprecated can be removed once TYPO3 8 support is removed
            $scriptDispatcher->addInstallerScript(new WebDirectory());
            $scriptDispatcher->addInstallerScript(new AutoloadConnector());
        }
    }
}
