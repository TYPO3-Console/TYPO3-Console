<?php
declare(strict_types=1);
namespace TYPO3Console\ExtCommand;

use TYPO3\CMS\Core\Console\CommandRegistry;

class ExtAlias extends ExtCommand
{
    /**
     * @var int
     */
    private static $instanceCount = 0;

    public function __construct(string $name = null, CommandRegistry $registry = null)
    {
        parent::__construct($name, $registry);
        self::$instanceCount++;
        if (self::$instanceCount > 1) {
            throw new \RuntimeException('Instance created multiple times', 1589023998);
        }
    }
}
