<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Tests\Unit\Mvc\Cli;

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

use Helhum\Typo3Console\Mvc\Cli\CommandConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;

class CommandConfigurationTest extends TestCase
{
    public function validationThrowsExceptionOnInvalidRegistrationDataProvider()
    {
        return [
            'commands not an array' => [
                [
                    'commands' => '',
                ],
            ],
        ];
    }

    /**
     * @param array $configuration
     * @test
     * @dataProvider validationThrowsExceptionOnInvalidRegistrationDataProvider
     */
    public function validationThrowsExceptionOnInvalidRegistration(array $configuration)
    {
        $this->expectException(RuntimeException::class);
        CommandConfiguration::ensureValidCommandRegistration($configuration, 'foo');
    }
}
