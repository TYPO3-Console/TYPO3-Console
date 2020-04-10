<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Install\Upgrade;

/*
 * This file is part of the TYPO3 Console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

class UpgradeWizardResult
{
    /**
     * @var array
     */
    private $messages;

    /**
     * @var bool
     */
    private $hasPerformed;

    /**
     * @var bool
     */
    private $succeeded;

    public function __construct(bool $hasPerformed, array $messages = [], bool $succeeded = false)
    {
        $this->hasPerformed = $hasPerformed;
        $this->messages = $messages;
        $this->succeeded = $hasPerformed && $succeeded;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function hasPerformed(): bool
    {
        return $this->hasPerformed;
    }

    public function hasErrored(): bool
    {
        return $this->hasPerformed && !$this->succeeded;
    }
}
