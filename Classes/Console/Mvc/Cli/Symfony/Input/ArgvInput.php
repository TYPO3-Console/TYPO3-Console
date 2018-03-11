<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Mvc\Cli\Symfony\Input;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Helmut Hummel <info@helhum.io>
 *  All rights reserved
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

class ArgvInput extends \Symfony\Component\Console\Input\ArgvInput
{
    /**
     * @return bool
     */
    public function hasGivenOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * @return bool
     */
    public function hasGivenArgument(string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }
}
