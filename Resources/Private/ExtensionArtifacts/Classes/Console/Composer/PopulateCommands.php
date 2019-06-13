<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Composer {
    use Composer\Script\Event;
    use Helhum\Typo3Console\Composer\InstallerScript\PopulateCommandConfiguration;

    class PopulateCommands
    {
        public static function populate(Event $event)
        {
            return (new PopulateCommandConfiguration())->run($event);
        }
    }
}

// Since we exclude the installer from being actually installed, we must define the interface here as well
namespace TYPO3\CMS\Composer\Plugin\Core {
    if (!interface_exists(InstallerScript::class)) {
        interface InstallerScript
        {
        }
    }
}
