<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Extension;

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

use Helhum\Typo3Console\Core\Booting\CompatibilityScripts;

/**
 * Trait for use with extension commands
 */
trait ShowDeprecationMessageTrait
{
    private function showDeprecationMessageIfApplicable()
    {
        if (CompatibilityScripts::isComposerMode()) {
            $this->output->writeln('<warning>This command is deprecated when TYPO3 is composer managed.</warning>');
            $this->output->writeln('<warning>It might lead to unexpected results.</warning>');
            $this->output->writeln('<warning>The PackageStates.php file that tracks which extension should be active,</warning>');
            $this->output->writeln('<warning>should be generated automatically using install:generatepackagestates.</warning>');
            $this->output->writeln('<warning>To set up all active extensions, extension:setupactive should be used.</warning>');
            $this->output->writeln('<warning>This command will be disabled, when TYPO3 is composer managed, in TYPO3 Console 6</warning>');
        }
    }
}
