<?php
namespace Helhum\Typo3Console\Command\Delegation;

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

use Helhum\Typo3Console\Service\Delegation\ReferenceIndexIntegrityDelegateInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\NullWriter;

/**
 * Class ReferenceIndexUpdateDelegate
 */
class ReferenceIndexUpdateDelegate implements ReferenceIndexIntegrityDelegateInterface
{
    /**
     * @var array
     */
    protected $subscribers = [];

    /**
     * @param string $name
     * @param array $arguments
     */
    public function emitEvent($name, $arguments = [])
    {
        if (empty($this->subscribers[$name])) {
            return;
        }

        foreach ($this->subscribers[$name] as $subscriber) {
            call_user_func_array($subscriber, $arguments);
        }
    }

    /**
     * @param string $name
     * @param Callback $subscriber
     */
    public function subscribeEvent($name, $subscriber)
    {
        if (!isset($this->subscribers[$name])) {
            $this->subscribers[$name] = [];
        }

        $this->subscribers[$name][] = $subscriber;
    }

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: $this->createNullLogger();
    }

    /**
     * @param int $unitsOfWorkCount
     * @return void
     */
    public function willStartOperation($unitsOfWorkCount)
    {
        $this->emitEvent('willStartOperation', [$unitsOfWorkCount]);
    }

    /**
     * @param string $tableName
     * @param array $record
     * @return void
     */
    public function willUpdateRecord($tableName, array $record)
    {
        $this->emitEvent('willUpdateRecord', [$tableName, $record]);
    }

    /**
     * @return void
     */
    public function operationHasEnded()
    {
        $this->emitEvent('operationHasEnded');
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return LoggerInterface
     */
    protected function createNullLogger()
    {
        $logger = new Logger(__CLASS__);
        $logger->addWriter(LogLevel::EMERGENCY, new NullWriter());
        return $logger;
    }
}
