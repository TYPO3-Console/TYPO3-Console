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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;
use TYPO3\CMS\Saltedpasswords\Salt\SaltInterface;

class CreateAdminCommand extends Command
{
    /**
     * @var SaltInterface
     */
    private $passwordHasher;

    public function __construct(string $name = null, SaltInterface $passwordHasher = null)
    {
        parent::__construct($name);
        $this->passwordHasher = $passwordHasher ?: SaltFactory::getSaltingInstance(null, 'BE');
    }

    protected function configure()
    {
        $this->setDescription('Create admin backend user');
        $this->setHelp('Create a new user with administrative access.');
        $this->addArgument(
            'username',
            InputArgument::REQUIRED,
            'Username of the user'
        );
        $this->addArgument(
            'password',
            InputArgument::REQUIRED,
            'Password of the user'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $givenUsername = $username;
        $username = strtolower(preg_replace('/\\s/i', '', $username));

        if ($givenUsername !== $username) {
            $io->warning(sprintf('Given username "%s" contains invalid characters. Using "%s" instead.', $givenUsername, $username));
        }

        if ($username === '') {
            $io->error('Username must have at least 1 character.');

            return 1;
        }
        if (strlen($password) < 8) {
            $io->error('Password must have at least 8 characters.');

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
            $io->error(sprintf('A user with username "%s" already exists.', $username));

            return 1;
        }
        $adminUserFields = [
            'username' => $username,
            'password' => $this->passwordHasher->getHashedPassword($password),
            'admin' => 1,
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'crdate' => $GLOBALS['EXEC_TIME'],
        ];
        $connectionPool->getConnectionForTable('be_users')
            ->insert('be_users', $adminUserFields);

        $io->success(sprintf('Created admin user with username "%s".', $username));
    }
}
