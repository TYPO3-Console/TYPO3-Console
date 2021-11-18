<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Composer\InstallerScript;

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

use Composer\Script\Event as ScriptEvent;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScript;

class WarnDevBranchInstall implements InstallerScript
{
    public function run(ScriptEvent $event): bool
    {
        $io = $event->getIO();
        $composer = $event->getComposer();

        $typo3Package = $composer->getRepositoryManager()->getLocalRepository()->findPackage('typo3/cms-core', 'dev-master');
        if ($typo3Package) {
            $io->warning(sprintf('You are installing a development version of TYPO3 ("%s").', $typo3Package->getVersion()));
            $io->warning('TYPO3 Console might work, but might also fail at any point.');
            $io->warning('Use at your own risk!');

            return $io->askConfirmation('<comment>I have read and understood, that errors may occur. (y/N)</comment> ', false);
        }

        return true;
    }
}
