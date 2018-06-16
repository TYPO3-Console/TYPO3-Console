<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Install\Action;

use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Symfony\Component\Console\Exception\RuntimeException;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Helmut Hummel <info@helhum.io>
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

class InstallActionFactory
{
    private static $typeClassMapping = [
        'prepareInstall' => PrepareInstallAction::class,
        'install' => Typo3InstallAction::class,
        'extensionSetup' => ExtensionSetupAction::class,
        'commands' => CommandsAction::class,
    ];

    /**
     * @var ConsoleOutput
     */
    private $output;

    /**
     * @var CommandDispatcher
     */
    private $commandDispatcher;

    public function __construct(ConsoleOutput $output, CommandDispatcher $commandDispatcher)
    {
        $this->output = $output;
        $this->commandDispatcher = $commandDispatcher;
    }

    public function create(string $type): InstallActionInterface
    {
        $action = null;

        if (isset(self::$typeClassMapping[$type]) && $this->isInstallAction(self::$typeClassMapping[$type])) {
            $action = new self::$typeClassMapping[$type]();
        }
        if ($action === null && $this->isInstallAction($type)) {
            $action = new $type();
        }

        if (!$action instanceof InstallActionInterface) {
            throw new RuntimeException(sprintf('Install actions must implement InstallActionInterface. Given type "%s" does not.', $type), 1529158153);
        }

        $action->setOutput($this->output);
        $action->setCommandDispatcher($this->commandDispatcher);

        return $action;
    }

    private function isInstallAction(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        return in_array(InstallActionInterface::class, class_implements($className), true);
    }
}
