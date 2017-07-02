<?php
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

use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;

class CacheCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function cacheCanBeFlushedFlushed()
    {
        $output = $this->commandDispatcher->executeCommand('cache:flush');
        $this->assertSame('Flushed all caches.', $output);
    }

    /**
     * @test
     */
    public function cacheCanBeForceFlushedFlushed()
    {
        $output = $this->commandDispatcher->executeCommand('cache:flush', ['--force' => true]);
        $this->assertSame('Force flushed all caches.', $output);
    }

    /**
     * @test
     */
    public function fileCachesCanBeFlushed()
    {
        $output = $this->commandDispatcher->executeCommand('cache:flush', ['--files-only' => true]);
        $this->assertSame('Flushed all file caches.', $output);
    }

    /**
     * @test
     */
    public function fileCachesCanBeForceFlushedFlushed()
    {
        $output = $this->commandDispatcher->executeCommand('cache:flush', ['--files-only' => true, '--force' => true]);
        $this->assertSame('Force flushed all file caches.', $output);
    }

    /**
     * @test
     */
    public function cacheGroupsCanBeFlushed()
    {
        $output = $this->commandDispatcher->executeCommand('cache:flushgroups', ['--groups' => 'pages']);
        $this->assertSame('Flushed all caches for group(s): "pages".', $output);
    }

    /**
     * @test
     */
    public function invalidGroupsMakesCommandFail()
    {
        try {
            $this->commandDispatcher->executeCommand('cache:flushgroups', ['--groups' => 'foo']);
        } catch (FailedSubProcessCommandException $e) {
            $this->assertSame(1, $e->getExitCode());
            $this->assertContains('Invalid cache groups "foo".', $e->getOutputMessage());
        }
    }

    /**
     * @test
     */
    public function cacheGroupsCanBeListed()
    {
        $output = $this->commandDispatcher->executeCommand('cache:listgroups');
        $this->assertContains('The following cache groups are registered: ', $output);
    }

    /**
     * @test
     */
    public function cacheTagsCanBeFlushed()
    {
        $output = $this->commandDispatcher->executeCommand('cache:flushtags', ['--tags' => 'foo']);
        $this->assertSame('Flushed caches by tags "foo".', $output);
    }

    /**
     * @test
     */
    public function cacheTagsAndGroupsCanBeFlushed()
    {
        $output = $this->commandDispatcher->executeCommand('cache:flushtags', ['--tags' => 'foo', '--groups' => 'pages']);
        $this->assertSame('Flushed caches by tags "foo" in groups: "pages".', $output);
    }
}
