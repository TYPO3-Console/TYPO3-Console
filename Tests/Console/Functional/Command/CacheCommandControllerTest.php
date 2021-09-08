<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Tests\Functional\Command;

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

class CacheCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function cacheGroupsCanBeListed()
    {
        $output = $this->executeConsoleCommand('cache:listgroups');
        $this->assertStringContainsString('The following cache groups are registered: ', $output);
    }

    /**
     * @test
     */
    public function cacheTagsCanBeFlushed()
    {
        $output = $this->executeConsoleCommand('cache:flushtags', ['foo']);
        $this->assertSame('Flushed caches by tags "foo".', $output);
    }

    /**
     * @test
     */
    public function cacheTagsAndGroupsCanBeFlushed()
    {
        $output = $this->executeConsoleCommand('cache:flushtags', ['foo', '--groups' => 'pages']);
        $this->assertSame('Flushed caches by tags "foo" in groups: "pages".', $output);
    }
}
