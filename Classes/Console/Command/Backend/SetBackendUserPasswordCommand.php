<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Backend;

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

use Helhum\Typo3Console\Exception\ArgumentValidationFailedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SetBackendUserPasswordCommand extends Command
{
    private $passwordAsArgument = true;

    protected function configure(): void
    {
        $this->setDescription('Set password for a backend user');
        $this->setHelp('Set a new password for an existing backend user. The user is looked up by username, including disabled and expired users.');
        $this->setDefinition(
            [
                new InputArgument(
                    'username',
                    InputArgument::REQUIRED,
                    'Username of the user'
                ),
                new InputArgument(
                    'password',
                    InputArgument::OPTIONAL,
                    'New password for the user'
                ),
            ]
        );
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        if (empty($input->getArgument('username'))) {
            $username = $io->ask(
                'Username',
                null,
                function ($username) {
                    if ($error = $this->validateUsername($username)) {
                        throw new ArgumentValidationFailedException($error);
                    }

                    return $username;
                }
            );
            $input->setArgument('username', $username);
        }
        if (empty($input->getArgument('password'))) {
            $password = $io->askHidden(
                'New password',
                function ($password) {
                    if ($error = $this->validatePassword($password)) {
                        throw new ArgumentValidationFailedException($error);
                    }

                    return $password;
                }
            );
            $this->passwordAsArgument = false;
            $input->setArgument('password', $password);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        if ($this->passwordAsArgument) {
            $output->writeln('<warning>Using a password on the command line interface can be insecure.</warning>');
        }
        if ($userError = $this->validateUsername($username)) {
            $output->writeln(sprintf('<error>%s</error>', $userError));
        }
        if ($passwordError = $this->validatePassword($password)) {
            $output->writeln(sprintf('<error>%s</error>', $passwordError));
        }
        if (isset($userError) || isset($passwordError)) {
            return 1;
        }
        $passwordHasher = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('BE');
        $hashedPassword = $passwordHasher->getHashedPassword($password);

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class)
            ->removeByType(HiddenRestriction::class);
        $affectedRows = $queryBuilder->update('be_users')
            ->set('password', $hashedPassword)
            ->set('tstamp', $GLOBALS['EXEC_TIME'])
            ->where(
                $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username))
            )->executeStatement();

        if ($affectedRows === 0) {
            $output->writeln(sprintf('<error>No user with username "%s" found. Password was not changed.</error>', $username));

            return 1;
        }

        $output->writeln(sprintf('<info>Password for user "%s" has been set.</info>', $username));

        return 0;
    }

    private function validateUsername(?string $username): ?string
    {
        if (empty($username)) {
            return 'Username must not be empty.';
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class)
            ->removeByType(HiddenRestriction::class);
        $userExists = $queryBuilder->count('uid')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username))
            )->executeQuery()->fetchOne() > 0;

        if (!$userExists) {
            return sprintf('A user with username "%s" does not exist.', $username);
        }

        return null;
    }

    private function validatePassword(?string $password): ?string
    {
        if (empty($password)) {
            return 'Password must not be empty.';
        }
        if (strlen($password) < 8) {
            return 'Password must have at least 8 characters.';
        }

        return null;
    }
}
