<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Core\Booting;

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

use Helhum\Typo3Console\Exception;

/**
 * When executing a step causes an exception to occur
 */
class StepFailedException extends Exception
{
    /**
     * @var Step
     */
    private $step;

    public function __construct(Step $step, \Throwable $previous)
    {
        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
        $this->step = $step;
    }

    public function getFailedStep(): Step
    {
        return $this->step;
    }
}
