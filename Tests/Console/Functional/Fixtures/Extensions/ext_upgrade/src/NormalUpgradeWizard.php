<?php
declare(strict_types = 1);
namespace Helhum\Typo3Console\Tests\Functional\Fixtures\Extensions\ext_upgrade\src;

use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

class NormalUpgradeWizard implements UpgradeWizardInterface
{
    public function getIdentifier(): string
    {
        return 'normalWizard';
    }

    public function getTitle(): string
    {
        return 'Just a regular wizard';
    }

    public function getDescription(): string
    {
        return 'Fly you fools';
    }

    public function executeUpdate(): bool
    {
        return true;
    }

    public function updateNecessary(): bool
    {
        return true;
    }

    public function getPrerequisites(): array
    {
        return [];
    }
}
