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

use Helhum\Typo3Console\Command\AbstractConvertedCommand;
use Helhum\Typo3Console\Database\Configuration\ConnectionConfiguration;
use Helhum\Typo3Console\Database\Process\MysqlCommand;
use Helhum\Typo3Console\Database\Schema\TableMatcher;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DatabaseExportCommand extends AbstractConvertedCommand
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
EOH
        );
        /** @deprecated Will be removed with 6.0 */
        $this->setDefinition($this->createCompleteInputDefinition());
    }

    /**
     * @deprecated Will be removed with 6.0
     */
    protected function createNativeDefinition(): array
    {
        return [
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
        ];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
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
            $mysqlCommand = new MysqlCommand($this->connectionConfiguration->build($mysqlConnectionName), [], $output);
            $exitCode = $mysqlCommand->mysqldump(
                $this->buildArguments($mysqlConnectionName, $excludes, $output),
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

    /**
     * @deprecated will be removed with 6.0
     *
     * @return array
     */
    protected function createDeprecatedDefinition(): array
    {
        return [
            new InputArgument(
                'excludeTables',
                null,
                'Comma-separated list of table names to exclude from the export. Wildcards are supported.',
                []
            ),
            new InputOption(
                'exclude-tables',
                null,
                InputOption::VALUE_REQUIRED,
                'Comma-separated list of table names to exclude from the export. Wildcards are supported.',
                []
            ),
        ];
    }

    /**
     * @deprecated will be removed with 6.0
     */
    protected function handleDeprecatedArgumentsAndOptions(InputInterface $input, OutputInterface $output)
    {
        $excludeTables = null;
        $messages = null;
        if ($input->getArgument('excludeTables')) {
            $excludeTables = explode(',', $input->getArgument('excludeTables'));
            $messages[] = '<warning>Passing excluded tables as argument is deprecated. Please use --exclude instead.</warning>';
        }
        if ($input->getOption('exclude-tables')) {
            $excludeTables = explode(',', $input->getOption('exclude-tables'));
            $messages[] = '<warning>Option --exclude-tables is deprecated. Please use --exclude for each exclude instead.</warning>';
        }
        if ($messages !== null && $excludeTables !== null) {
            $input->setOption('exclude', $excludeTables);
            if ($output instanceof ConsoleOutput) {
                $output->getErrorOutput()->writeln($messages);
            }
        }
    }
}
