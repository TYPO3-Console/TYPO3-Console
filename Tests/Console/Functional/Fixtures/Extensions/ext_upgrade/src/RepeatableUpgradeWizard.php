<?php
declare(strict_types = 1);
namespace Helhum\Typo3Console\Tests\Functional\Fixtures\Extensions\ext_upgrade\src;

use TYPO3\CMS\Install\Updates\RepeatableInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

class RepeatableUpgradeWizard implements UpgradeWizardInterface, RepeatableInterface
{
    public function getIdentifier(): string
    {
        return 'repeatableWizard';
    }

    public function getTitle(): string
    {
        return 'A repeatable wizard';
    }

    public function getDescription(): string
    {
        return 'It is not despair, for despair is only for those who see the end beyond all doubt. We do not.';
    }

    public function executeUpdate(): bool
    {
        return true;
    }

    public function updateNecessary(): bool
    {
        return getenv('TYPO3_CONSOLE_DISABLE_REPEATABLE_WIZARD') === false;
    }

    public function getPrerequisites(): array
    {
        return [];
    }
}
