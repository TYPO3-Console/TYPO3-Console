<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Database;

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

use Helhum\Typo3Console\Database\Configuration\ConnectionConfiguration;
use Helhum\Typo3Console\Database\Process\MysqlCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseImportCommand extends Command
{
    public function __construct(private readonly bool $applicationIsReady, private readonly ConnectionConfiguration $connectionConfiguration)
    {
        parent::__construct('database:import');
    }

    public function isEnabled(): bool
    {
        return $this->applicationIsReady || getenv('TYPO3_CONSOLE_RENDERING_REFERENCE') !== false;
    }

    protected function configure(): void
    {
        $this->setDescription('Import mysql queries from stdin');
        $this->setHelp(
            <<<'EOH'
This means that this can not only be used to pass insert statements,
it but works as well to pass SELECT statements to it.
The mysql binary must be available in the path for this command to work.
This obviously only works when MySQL is used as DBMS.

<b>Example (import):</b>

  <code>ssh remote.server '/path/to/typo3 database:export' | %command.full_name%</code>

<b>Example (select):</b>

  <code>echo 'SELECT username from be_users WHERE admin=1;' | %command.full_name% -- --skip-ssl</code>

<b>Example (interactive):</b>

  <code>%command.full_name% --interactive</code>
EOH
        );
        $this->setDefinition([
            new InputOption(
                'interactive',
                '',
                InputOption::VALUE_NONE,
                'Open an interactive mysql shell using the TYPO3 connection settings.'
            ),
            new InputOption(
                'connection',
                '',
                InputOption::VALUE_REQUIRED,
                'TYPO3 database connection name',
                'Default'
            ),
            new InputArgument(
                'additionalMysqlArguments',
                InputArgument::IS_ARRAY,
                'Pass one or more additional arguments to the mysql command; see examples',
                []
            ),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $additionalMysqlArguments = $input->getArgument('additionalMysqlArguments');
        $interactive = $input->getOption('interactive');
        $connection = (string)$input->getOption('connection');

        $availableConnectionNames = $this->connectionConfiguration->getAvailableConnectionNames('mysql');
        if (empty($availableConnectionNames) || !in_array($connection, $availableConnectionNames, true)) {
            $output->writeln('<error>No suitable MySQL connection found for import.</error>');

            return 2;
        }

        $mysqlCommand = new MysqlCommand($this->connectionConfiguration->build($connection), $output);
        $exitCode = $mysqlCommand->mysql(
            array_merge($interactive ? [] : ['--skip-column-names'], $additionalMysqlArguments),
            STDIN,
            null,
            $interactive
        );

        return $exitCode;
    }
}
