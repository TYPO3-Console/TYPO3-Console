<?php
namespace Helhum\Typo3Console\Service\Configuration\ConsoleRenderer;

/*
 * This file is part of the TYPO3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use cogpowered\FineDiff\Diff;
use cogpowered\FineDiff\Granularity\Paragraph;
use TYPO3\CMS\Core\Utility\ArrayUtility;

class ConsoleRenderer
{
    public function render($config)
    {
        return $this->getConfigurationAsString($config);
    }

    public function renderDiff($localConfig, $activeConfig)
    {
        $diff = new Diff(new Paragraph(), new DiffConsoleRenderer());

        $result = '<del>-- LocalConfiguration.php</del>' . PHP_EOL;
        $result .= '<ins>++ AdditionalConfiguration.php</ins>' . PHP_EOL;

        $result .= $diff->render($this->getConfigurationAsString($localConfig), $this->getConfigurationAsString($activeConfig));

        return $result;
    }

    protected function getConfigurationAsString($config)
    {
        return ArrayUtility::arrayExport([$config]);
    }
}
