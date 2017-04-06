<?php
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

use TYPO3\CMS\Core\Core\Bootstrap;

/**
 * A Step within a Sequence
 *
 * @api
 */
class Step
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @param string $identifier
     * @param mixed $callback
     */
    public function __construct($identifier, $callback)
    {
        $this->identifier = $identifier;
        $this->callback = $callback;
    }

    /**
     * Invokes / executes this step
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public function __invoke(Bootstrap $bootstrap)
    {
        call_user_func($this->callback, $bootstrap);
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
}
