<?php
declare(strict_types = 1);
namespace Helhum\Typo3Console\Tests\Functional\Fixtures\Extensions\ext_upgrade\src;

use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\Confirmation;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

class ConfirmableUpgradeWizard implements UpgradeWizardInterface, ConfirmableInterface
{
    public function getIdentifier(): string
    {
        return 'confirmableWizard';
    }

    public function getTitle(): string
    {
        return 'A wizard requiring confirmation';
    }

    public function getDescription(): string
    {
        return 'I will not say: do not weep; for not all tears are an evil.';
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

    public function getConfirmation(): Confirmation
    {
        return new Confirmation(
            'Many that live deserve death.',
            'And some that die deserve life.',
            false,
            'Can you give it to them?',
            'Then do not be too eager to deal out death in judgement.',
            true
        );
    }
}
