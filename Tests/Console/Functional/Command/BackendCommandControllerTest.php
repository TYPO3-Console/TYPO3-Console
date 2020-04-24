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

use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;

class BackendCommandControllerTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function backendCanBeLockedAndUnlocked()
    {
        $output = $this->executeConsoleCommand('backend:lock');
        $this->assertContains('Backend has been locked', $output);
        $output = $this->executeConsoleCommand('backend:lock');
        $this->assertContains('Backend is already locked', $output);
        $output = $this->executeConsoleCommand('backend:unlock');
        $this->assertContains('Backend lock is removed', $output);
        $output = $this->executeConsoleCommand('backend:unlock');
        $this->assertContains('Backend is already unlocked', $output);
    }

    /**
     * @test
     */
    public function backendCanBeLockedAndUnlockedForEditors()
    {
        $output = $this->executeConsoleCommand('backend:lockforeditors');
        $this->assertContains('Locked backend for editor access', $output);
        $output = $this->executeConsoleCommand('backend:lockforeditors');
        $this->assertContains('The backend was already locked for editors', $output);
        $output = $this->executeConsoleCommand('backend:unlockforeditors');
        $this->assertContains('Unlocked backend for editors', $output);
        $output = $this->executeConsoleCommand('backend:unlockforeditors');
        $this->assertContains('The backend was not locked for editors', $output);
    }

    public function createAdminUserRemovesSpacesFromUserNameDataProvider(): array
    {
        return [
           'Space in between' => [
               'test_administra tor1243',
           ],
           'Space in between and beginning' => [
               ' test_administra tor1243',
           ],
           'Space in between and end' => [
               'test_administra tor1243 ',
           ],
           'Space in between and beginning and end' => [
               ' test_administra tor1243 ',
           ],
           'With uppercase' => [
               'test_AdmiNIStratOr',
           ],
        ];
    }

    /**
     * @test
     * @dataProvider createAdminUserRemovesSpacesFromUserNameDataProvider
     * @param string $username
     */
    public function createAdminUserFailsCreatingUsersWithInvalidCharacters(string $username): void
    {
        try {
            $this->commandDispatcher->executeCommand('backend:createadmin', [$username, 'password']);
            $this->fail(sprintf('Creating user %s should have failed', $username));
        } catch (FailedSubProcessCommandException $e) {
            $this->assertContains('No special characters are allowed in username', $e->getOutputMessage());
        } finally {
            $this->executeMysqlQuery('DELETE FROM be_users WHERE username LIKE "test\_%"');
        }
    }

    public function createAdminUserCreatesUserWithSpecialCharactersInUserNameDataProvider(): array
    {
        return [
           'Like email' => [
               'test_administra@foo.de',
           ],
           'With ampersand' => [
               'test_bla&blupp',
           ],
           'With caret and percent' => [
               'test_^%test',
           ],
        ];
    }

    /**
     * @test
     * @dataProvider createAdminUserCreatesUserWithSpecialCharactersInUserNameDataProvider
     * @param string $username
     */
    public function createAdminUserCreatesUserWithSpecialCharactersInUserName(string $username): void
    {
        try {
            $this->executeConsoleCommand('backend:createadmin', [$username, 'password']);
            $queryResult = $this->executeMysqlQuery('SELECT username FROM be_users WHERE username="' . $username . '"');
            $this->assertSame($username, trim($queryResult));
        } finally {
            $this->executeMysqlQuery('DELETE FROM be_users WHERE username LIKE "test\_%"');
        }
    }

    public function createAdminUserCreatesUserWithShortNamesDataProvider(): array
    {
        return [
           '3 chars' => [
               'foo',
           ],
           '2 chars' => [
               'fo',
           ],
           '1 char' => [
               'f',
           ],
        ];
    }

    /**
     * @test
     * @dataProvider createAdminUserCreatesUserWithShortNamesDataProvider
     * @param string $username
     */
    public function createAdminUserCreatesUserWithShortNames(string $username): void
    {
        $this->executeConsoleCommand('backend:createadmin', [$username, 'password']);
        $queryResult = $this->executeMysqlQuery(sprintf('SELECT username FROM be_users WHERE username="%s";', $username));
        $this->assertSame($username, trim($queryResult));
        $this->executeMysqlQuery(sprintf('DELETE FROM be_users WHERE username="%s"', $username));
    }

    /**
     * @test
     */
    public function adminUserWithTooShortUsernameWillBeRejected(): void
    {
        try {
            $this->commandDispatcher->executeCommand('backend:createadmin', ['', 'bar', '--no-interaction']);
            $this->fail('Command did not fail as expected (user is created)');
        } catch (FailedSubProcessCommandException $e) {
            $this->assertContains('Username must not be empty', $e->getOutputMessage());
        }
    }

    /**
     * @test
     */
    public function adminUserWithTooShortPasswordWillBeRejected(): void
    {
        try {
            $this->commandDispatcher->executeCommand('backend:createadmin', ['foobar', 'baz']);
            $this->fail('Command did not fail as expected (user is created)');
        } catch (FailedSubProcessCommandException $e) {
            $this->assertContains('Password must have at least 8 characters', $e->getOutputMessage());
        }
    }

    /**
     * @test
     */
    public function adminUserWithValidCredentialsWillBeCreated(): void
    {
        $output = $this->executeConsoleCommand('backend:createadmin', ['administrator', 'password']);
        $this->assertContains('Created admin user with username "administrator"', $output);
        $queryResult = $this->executeMysqlQuery('SELECT username FROM be_users WHERE username="administrator"');
        $this->assertSame('administrator', trim($queryResult));
    }

    /**
     * @test
     */
    public function adminUserWithValidCredentialsWillNotBeCreatedIfUsernameAlreadyExists(): void
    {
        try {
            $this->commandDispatcher->executeCommand('backend:createadmin', ['administrator', 'password2']);
            $this->fail('Command did not fail as expected (user is created)');
        } catch (FailedSubProcessCommandException $e) {
            $this->assertContains('A user with username "administrator" already exists', $e->getOutputMessage());
        } finally {
            $this->executeMysqlQuery('DELETE FROM be_users WHERE username="administrator"');
        }
    }
}
