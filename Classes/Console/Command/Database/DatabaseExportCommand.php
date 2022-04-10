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
use Helhum\Typo3Console\Database\Schema\TableMatcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DatabaseExportCommand extends Command
{
    /**
     * @var ConnectionConfiguration
     */
    private $connectionConfiguration;

    public function __construct(string $name = null, ConnectionConfiguration $connectionConfiguration = null)
    {
        parent::__construct($name);
        $this->connectionConfiguration = $connectionConfiguration ?: new ConnectionConfiguration();
    }

    protected function configure()
    {
        $this->setDescription('Export database to stdout');
        $this->setHelp(
            <<<'EOH'
Export the database (all tables) directly to stdout.
The mysqldump binary must be available in the path for this command to work.
This obviously only works when MySQL is used as DBMS.

Tables to be excluded from the export can be specified fully qualified or with wildcards:

<b>Example:</b>

  <code>%command.full_name% -c Default -e 'cf_*' -e 'cache_*' -e '[bf]e_sessions' -e sys_log</code>
  <code>%command.full_name% database:export -c Default -- --column-statistics=0</code>
EOH
        );
        $this->setDefinition([
            new InputOption(
                'exclude',
                '-e',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Full table name or wildcard expression to exclude from the export.',
                []
            ),
            new InputOption(
                'connection',
                '-c',
                InputOption::VALUE_REQUIRED,
                'TYPO3 database connection name (defaults to all configured MySQL connections)',
                null
            ),
            new InputArgument(
                'additionalMysqlDumpArguments',
                InputArgument::IS_ARRAY,
                'Pass one or more additional arguments to the mysqldump command; see examples',
                []
            ),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $additionalMysqlDumpArguments = $input->getArgument('additionalMysqlDumpArguments');
        $connection = $input->getOption('connection');
        $excludes = $input->getOption('exclude');

        $availableConnectionNames = $connectionNames = $this->connectionConfiguration->getAvailableConnectionNames('mysql');
        $failureReason = '';
        if ($connection !== null) {
            $availableConnectionNames = array_intersect($connectionNames, [$connection]);
            $failureReason = sprintf(' Given connection "%s" is not configured as MySQL connection.', $connection);
        }
        if (empty($availableConnectionNames)) {
            $output->writeln(sprintf('<error>No MySQL connections found to export.%s</error>', $failureReason));

            return 2;
        }

        foreach ($availableConnectionNames as $mysqlConnectionName) {
            $mysqlCommand = new MysqlCommand($this->connectionConfiguration->build($mysqlConnectionName), $output);
            $exitCode = $mysqlCommand->mysqldump(
                array_merge($this->buildArguments($mysqlConnectionName, $excludes, $output), $additionalMysqlDumpArguments),
                null,
                $mysqlConnectionName
            );

            if ($exitCode !== 0) {
                $output->writeln(sprintf('<error>Could not dump SQL for connection "%s",</error>', $mysqlConnectionName));

                return $exitCode;
            }
        }

        return 0;
    }

    private function buildArguments(string $mysqlConnectionName, array $excludes, OutputInterface $output): array
    {
        $dbConfig = $this->connectionConfiguration->build($mysqlConnectionName);
        $arguments = [
            '--opt',
            '--single-transaction',
            '--no-tablespaces',
        ];

        if ($output->isVerbose()) {
            $arguments[] = '--verbose';
        }

        foreach ($this->matchTables($excludes, $mysqlConnectionName) as $table) {
            $arguments[] = sprintf('--ignore-table=%s.%s', $dbConfig['dbname'], $table);
        }

        return $arguments;
    }

    private function matchTables(array $excludes, string $connection): array
    {
        if (empty($excludes)) {
            return [];
        }
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName($connection);

        return (new TableMatcher())->match($connection, ...$excludes);
    }
}
