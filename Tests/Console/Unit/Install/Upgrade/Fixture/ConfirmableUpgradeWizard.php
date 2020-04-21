<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Tests\Unit\Install\Upgrade\Fixture;

/*
 * This file is part of the TYPO3 Console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\Confirmation;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

class ConfirmableUpgradeWizard implements UpgradeWizardInterface, ConfirmableInterface
{
    /**
     * @var bool
     */
    private $needsUpdate;

    /**
     * @var bool
     */
    private $succeedsUpdate;

    /**
     * @var bool
     */
    private $requiresConfirmation;

    /**
     * @var bool
     */
    private $default;

    public function __construct(bool $needsUpdate = true, bool $succeedsUpdate = true, bool $requiresConfirmation = false, bool $default = false)
    {
        $this->needsUpdate = $needsUpdate;
        $this->succeedsUpdate = $succeedsUpdate;
        $this->requiresConfirmation = $requiresConfirmation;
        $this->default = $default;
    }

    public function getIdentifier(): string
    {
        return self::class;
    }

    public function getTitle(): string
    {
        return self::class;
    }

    public function getDescription(): string
    {
        return self::class;
    }

    public function executeUpdate(): bool
    {
        return $this->succeedsUpdate;
    }

    public function updateNecessary(): bool
    {
        return $this->needsUpdate;
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    public function getConfirmation(): Confirmation
    {
        return new Confirmation(
            'confirm',
            'message',
            $this->default,
            'Y',
            'N',
            $this->requiresConfirmation
        );
    }
}
