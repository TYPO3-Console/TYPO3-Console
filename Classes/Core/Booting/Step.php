<?php
namespace Helhum\Typo3Console\Core\Booting;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

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
     * @var Callable
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
