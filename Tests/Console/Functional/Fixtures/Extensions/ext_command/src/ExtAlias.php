<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Tests\Functional\Fixtures\Extensions\ext_command\src;

use TYPO3\CMS\Core\Console\CommandRegistry;

class ExtAlias extends ExtCommand
{
    /**
     * @var int
     */
    private static $instanceCount = 0;

    public function __construct(string $name = null, CommandRegistry $registry = null)
    {
        if (getenv('THROWS_CONSTRUCT_EXCEPTION')) {
            throw new \Exception('Error occurred during object creation', 1589036051);
        }
        parent::__construct($name, $registry);
        self::$instanceCount++;
        if (self::$instanceCount > 1) {
            throw new \RuntimeException('Instance created multiple times', 1589023998);
        }
        if (!isset($GLOBALS['TCA'])) {
            // We check whether the command is instantiated after TYPO3 boot.
            throw new \RuntimeException('Instance created before boot', 1589025618);
        }
    }
}
