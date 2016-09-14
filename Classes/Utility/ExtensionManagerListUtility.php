<?php
namespace Helhum\Typo3Console\Utility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Extensionmanager\Utility\ListUtility;

/**
 * Extension manager list utility
 */
class ExtensionManagerListUtility extends ListUtility
{

    /**
     * Emits packages may have changed signal
     */
    protected function emitPackagesMayHaveChangedSignal()
    {
        // Disable signal to prevent writing of file "LocalConfiguration.php"
    }


}
