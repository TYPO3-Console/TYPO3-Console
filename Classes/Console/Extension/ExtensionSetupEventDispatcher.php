<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Extension;

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

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Proxy class to handle events
 */
class ExtensionSetupEventDispatcher implements EventDispatcherInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $parentEventDispatcher;

    private $additionalListeners = [];

    public function __construct(EventDispatcherInterface $parentEventDispatcher)
    {
        $this->parentEventDispatcher = $parentEventDispatcher;
    }

    public function updateParentEventDispatcher(EventDispatcherInterface $parentEventDispatcher): void
    {
        $this->parentEventDispatcher = $parentEventDispatcher;
    }

    public function dispatch(object $event)
    {
        $eventClass = get_class($event);
        if (isset($this->additionalListeners[$eventClass])) {
            foreach ($this->additionalListeners[$eventClass] as $callable) {
                $callable($event);
            }
        }

        return $this->parentEventDispatcher->dispatch($event);
    }

    public function addListener(string $eventClass, callable $callable): void
    {
        $this->additionalListeners[$eventClass][] = $callable;
    }
}
