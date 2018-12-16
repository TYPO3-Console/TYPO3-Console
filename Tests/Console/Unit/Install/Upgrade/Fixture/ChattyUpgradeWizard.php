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

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

class ChattyUpgradeWizard implements UpgradeWizardInterface, ChattyInterface
{
    /**
     * @var bool
     */
    private $needsUpdate;

    /**
     * @var bool
     */
    private $succeedsUpdate;

    public function __construct(bool $needsUpdate = true, bool $succeedsUpdate = true)
    {
        $this->needsUpdate = $needsUpdate;
        $this->succeedsUpdate = $succeedsUpdate;
    }

    /**
     * @var OutputInterface
     */
    private $output;

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
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
        $this->output->write('executeUpdate');

        return $this->succeedsUpdate;
    }

    public function updateNecessary(): bool
    {
        $this->output->write('updateNecessary');

        return $this->needsUpdate;
    }

    public function getPrerequisites(): array
    {
        return [];
    }
}
