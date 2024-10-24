<?php
declare(strict_types=1);
namespace Typo3Console\SQLCommand\Command;

/*
 * This file is part of the TYPO3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\ForwardCompatibility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SqlCommand extends Command
{
    public function isEnabled(): bool
    {
        return getenv('TYPO3_CONSOLE_RENDERING_REFERENCE') === false;
    }

    protected function configure(): void
    {
        $this->addOption(
            'no-db',
            null,
            InputOption::VALUE_NONE,
            ''
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sql = '';
        while ($f = fgets(STDIN)) {
            $sql .= $f;
        }

        $connection = DriverManager::getConnection($this->getConnectionParams($input));
        $result = $connection->executeQuery($sql);
        if (stripos($sql, 'select ') === 0) {
            $this->renderResult($result, $output);
        }

        return 0;
    }

    private function getConnectionParams(InputInterface $input): array
    {
        $params = [
            'dbname' => getenv('TYPO3_INSTALL_DB_DBNAME'),
            'driver' => getenv('TYPO3_INSTALL_DB_DRIVER'),
            'host' => getenv('TYPO3_INSTALL_DB_HOST'),
            'password' => getenv('TYPO3_INSTALL_DB_PASSWORD'),
            'port' => (int)(getenv('TYPO3_INSTALL_DB_PORT') ?: 3306),
            'user' => getenv('TYPO3_INSTALL_DB_USER'),
        ];
        if ($input->getOption('no-db')) {
            unset($params['dbname']);
        }
        if ($this->runsOnSqlite()) {
            $params['path'] = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['path'] ?? '';
        }

        return $params;
    }

    /**
     * @param ForwardCompatibility\DriverStatement|ForwardCompatibility\DriverResultStatement $result
     */
    private function renderResult($result, OutputInterface $output): void
    {
        $table = $this->createTable($output);
        while ($row = $result->fetchAssociative()) {
            $table->addRow($row);
        }
        $table->render();
    }

    private function createTable(OutputInterface $output): Table
    {
        $table = new Table($output);
        $style = new TableStyle();
        $style
            ->setHorizontalBorderChars(' ', ' ')
            ->setVerticalBorderChars(' ', ' ')
            ->setCrossingChars('', '', '', '', '', '', '', '', '');

        return $table->setStyle($style);
    }

    private function runsOnSqlite(): bool
    {
        return getenv('TYPO3_INSTALL_DB_DRIVER') === 'pdo_sqlite';
    }
}
