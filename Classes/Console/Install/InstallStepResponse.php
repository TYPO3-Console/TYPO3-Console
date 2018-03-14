<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Install;

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

use TYPO3\CMS\Install\Status\StatusInterface;

/**
 * Response of an install step
 */
class InstallStepResponse
{
    /**
     * @var bool
     */
    private $actionNeedsExecution;

    /**
     * @var StatusInterface[]
     */
    private $messages = [];

    /**
     * @var bool
     */
    private $actionNeedsReevaluation;

    /**
     * @param bool $actionNeedsExecution
     * @param StatusInterface[] $messages
     * @param bool $actionNeedsReevaluation
     */
    public function __construct($actionNeedsExecution, array $messages, $actionNeedsReevaluation = false)
    {
        $this->actionNeedsExecution = (bool)$actionNeedsExecution;
        $this->messages = $messages;
        $this->actionNeedsReevaluation = (bool)$actionNeedsReevaluation;
    }

    /**
     * @return bool
     */
    public function actionNeedsExecution()
    {
        return $this->actionNeedsExecution;
    }

    /**
     * @return StatusInterface[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @return bool
     */
    public function actionNeedsReevaluation()
    {
        return $this->actionNeedsReevaluation;
    }
}
