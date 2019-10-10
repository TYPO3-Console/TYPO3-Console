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

use Helhum\Typo3Console\Command\AbstractConvertedCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;

class CreateBackendAdminUserCommand extends AbstractConvertedCommand
{
    protected function configure()
    {
        $this->setDescription('Create admin backend user');
        $this->setHelp('Create a new user with administrative access.');
        /** @deprecated Will be removed with 6.0 */
        $this->setDefinition($this->createCompleteInputDefinition());
    }

    /**
     * @deprecated Will be removed with 6.0
     */
    protected function createNativeDefinition(): array
    {
        return [
            new InputArgument(
                'username',
                InputArgument::REQUIRED,
                'Username of the user'
            ),
            new InputArgument(
                'password',
                InputArgument::REQUIRED,
                'Password of the user'
            ),
        ];
    }

    /**
     * @deprecated will be removed with 6.0
     */
    protected function handleDeprecatedArgumentsAndOptions(InputInterface $input, OutputInterface $output)
    {
        // nothing to do here
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $passwordHasher = SaltFactory::getSaltingInstance(null, 'BE');
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $givenUsername = $username;
        $username = strtolower(preg_replace('/\\s/i', '', $username));

        if ($givenUsername !== $username) {
            $output->writeln(sprintf('<warning>Given username "%s" contains invalid characters. Using "%s" instead.</warning>', $givenUsername, $username));
        }

        if ($username === '') {
            $output->writeln('<error>Username must have at least 1 character.</error>');

            return 1;
        }
        if (strlen($password) < 8) {
            $output->writeln('<error>Password must have at least 8 characters.</error>');

            return 1;
        }
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $userExists = $connectionPool->getConnectionForTable('be_users')
            ->count(
                'uid',
                'be_users',
                ['username' => $username]
            );
        if ($userExists) {
            $output->writeln(sprintf('<error>A user with username "%s" already exists.</error>', $username));

            return 1;
        }
        $adminUserFields = [
            'username' => $username,
            'password' => $passwordHasher->getHashedPassword($password),
            'admin' => 1,
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'crdate' => $GLOBALS['EXEC_TIME'],
        ];
        $connectionPool->getConnectionForTable('be_users')
            ->insert('be_users', $adminUserFields);

        $output->writeln(sprintf('<info>Created admin user with username "%s".</info>', $username));

        return 0;
    }
}
