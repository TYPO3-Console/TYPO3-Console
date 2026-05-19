<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Database\Configuration;

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

use Helhum\Typo3Console\Database\Schema\TableMatcher;
use TYPO3\CMS\Core\Database\Connection;

final class MysqlCliConfiguration
{
    private const DEFAULT_OPTIONS = [
        'database:import' => [
            'binary' => 'mysql',
            'useSystemDefaults' => false,
            'arguments' => [],
            'additionalArguments' => [],
        ],
        'database:export' => [
            'binary' => 'mysqldump',
            'useSystemDefaults' => false,
            'arguments' => [
                '--opt',
                '--single-transaction',
                '--no-tablespaces',
            ],
            'additionalArguments' => [],
        ],
    ];

    private readonly ?string $host;

    private readonly ?int $port;

    private readonly ?string $socket;

    private readonly ?string $username;

    private readonly ?string $password;

    private readonly ?string $databaseName;

    private readonly string $binaryName;

    private readonly bool $useSystemDefaults;

    private readonly array $options;

    private ?string $mysqlTempFile = null;

    public function __construct(
        private readonly Connection $connection,
        public readonly string $name,
        string $scope,
    ) {
        $connectionParams = $connection->getParams();
        $this->options = $this->getOptions($scope, $name);
        $this->host = $connectionParams['host'] ?? null;
        $this->port = isset($connectionParams['port']) ? (int)$connectionParams['port'] : null;
        $this->socket = $connectionParams['unix_socket'] ?? null;
        $this->databaseName = $connectionParams['dbname'] ?? null;
        $this->username = $connectionParams['user'] ?? null;
        $this->password = $connectionParams['password'] ?? null;
        $this->useSystemDefaults = $this->options['useSystemDefaults'] ?? false;
        $this->binaryName = $this->options['binary'];
    }

    public function __wakeup()
    {
        throw new \RuntimeException(self::class . ' must not be unserialized', 1779092355);
    }

    private function getOptions(string $scope, string $name): array
    {
        if (!isset(self::DEFAULT_OPTIONS[$scope])) {
            throw new \UnexpectedValueException('Unknown scope: ' . $scope, 1779099932);
        }

        return array_replace(
            self::DEFAULT_OPTIONS[$scope],
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['typo3_console']['commandOptions'][$scope][$name]
                ?? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['typo3_console']['commandOptions'][$scope]['Default']
                ?? []
        );
    }

    public function buildArguments(array $additionalArguments = [], array $excludes = []): array
    {
        $additionalArguments = array_merge($this->options['arguments'], $this->options['additionalArguments'], $additionalArguments);
        foreach ($this->matchTables($excludes) as $table) {
            $additionalArguments[] = sprintf('--ignore-table=%s.%s', $this->databaseName, $table);
        }

        return array_merge(
            [$this->binaryName],
            $this->buildConnectionArguments(),
            $additionalArguments,
        );
    }

    private function buildConnectionArguments(): array
    {
        if ($configFile = $this->createTemporaryMysqlConfigurationFile()) {
            $configFileArgument = '--defaults-file=' . $configFile;
            if ($this->useSystemDefaults) {
                $configFileArgument = '--defaults-extra-file=' . $configFile;
            }
            $arguments[] = $configFileArgument;
        }
        if ($this->host !== null) {
            $arguments[] = '-h';
            $arguments[] = $this->host;
        }
        if ($this->port !== null) {
            $arguments[] = '-P';
            $arguments[] = $this->port;
        }
        if ($this->socket !== null) {
            $arguments[] = '-S';
            $arguments[] = $this->socket;
        }
        $arguments[] = $this->databaseName;

        return $arguments;
    }

    private function matchTables(array $excludes): array
    {
        if ($excludes === []) {
            return [];
        }

        return (new TableMatcher())->match($this->connection, ...$excludes);
    }

    private function createTemporaryMysqlConfigurationFile(): ?string
    {
        if ($this->username === null && $this->password === null) {
            return null;
        }
        if ($this->mysqlTempFile !== null) {
            return $this->mysqlTempFile;
        }
        $this->mysqlTempFile = tempnam(sys_get_temp_dir(), 'typo3_console_my_cnf_');
        $userDefinition = '';
        $passwordDefinition = '';
        if ($this->username !== null) {
            $userDefinition = sprintf('user="%s"', addcslashes($this->username, '"\\'));
        }
        if ($this->password !== null) {
            $passwordDefinition = sprintf('password="%s"', addcslashes($this->password, '"\\'));
        }
        $confFileContent = <<<EOF
[client]
$userDefinition
$passwordDefinition
EOF;
        (bool)file_put_contents($this->mysqlTempFile, $confFileContent);
        register_shutdown_function('unlink', $this->mysqlTempFile);

        return $this->mysqlTempFile;
    }
}
