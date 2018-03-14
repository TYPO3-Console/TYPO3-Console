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

class CleanupCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function referenceIndexIsUpdated()
    {
        $output = $this->executeConsoleCommand('cleanup:updatereferenceindex');
        $this->assertContains('Updating reference index', $output);
    }

    /**
     * @test
     */
    public function referenceIndexIsUpdatedWithNewReferences()
    {
        $this->executeMysqlQuery(
            'INSERT INTO `sys_category` (`pid`, `title`, `description`, `parent`, `items`, `l10n_diffsource`)'
            . ' VALUES'
            . ' (1, \'foobar\', \'\', 0, 1, \'\');'
        );
        $this->executeMysqlQuery(
            'INSERT INTO `sys_category_record_mm` (`uid_local`, `uid_foreign`, `tablenames`, `fieldname`, `sorting`, `sorting_foreign`)'
            . ' VALUES'
            . ' (1, 1, \'pages\', \'categories\', 0, 1);'
        );
        $output = $this->executeConsoleCommand('cleanup:updatereferenceindex');
        $this->assertContains('Updating reference index', $output);
        $this->assertContains('were fixed, while updating reference index for', $output);
    }

    /**
     * @test
     */
    public function referenceIndexIsUpdatedWithDeletedReferences()
    {
        $this->executeMysqlQuery('TRUNCATE `sys_category`;');
        $this->executeMysqlQuery('TRUNCATE `sys_category_record_mm`;');
        $output = $this->executeConsoleCommand('cleanup:updatereferenceindex');
        $this->assertContains('Updating reference index', $output);
        $this->assertContains('were fixed, while updating reference index for', $output);
    }
}
