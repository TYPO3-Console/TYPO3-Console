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

use Helhum\Typo3Console\Database\Schema\SchemaUpdate;
use Helhum\Typo3Console\Database\Schema\SchemaUpdateResultRenderer;
use Helhum\Typo3Console\Database\Schema\SchemaUpdateType;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Helhum\Typo3Console\Service\Database\SchemaService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;

class DatabaseUpdateSchemaCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Update database schema (TYPO3 Database Compare)');
        $this->setHelp(
            <<<'EOH'
Compares the current database schema with schema definition
from extensions's ext_tables.sql files and updates the schema based on the definition.

Valid schema update types are:

- field.add
- field.change
- field.prefix
- field.drop
- table.add
- table.change
- table.prefix
- table.drop
- safe (includes all necessary operations, to add or change fields or tables)
- destructive (includes all operations which rename or drop fields or tables)

The list of schema update types supports wildcards to specify multiple types, e.g.:

- "<code>*</code>" (all updates)
- "<code>field.*</code>" (all field updates)
- "<code>*.add,*.change</code>" (all add/change updates)

To avoid shell matching all types with wildcards should be quoted.

<b>Example:</b>

  <code>%command.full_name% "*.add,*.change"</code>
  <code>%command.full_name% "*.add" --raw</code>
  <code>%command.full_name% "*" --verbose</code>
EOH
        );
        $this->setDefinition(
            [
                new InputArgument(
                    'schemaUpdateTypes',
                    InputArgument::OPTIONAL,
                    'List of schema update types',
                    'safe'
                ),
                new InputOption(
                    'dry-run',
                    '',
                    InputOption::VALUE_NONE,
                    'If set the updates are only collected and shown, but not executed'
                ),
                new InputOption(
                    'raw',
                    '',
                    InputOption::VALUE_NONE,
                    'If set, only the SQL statements, that are required to update the schema, are printed'
                ),
            ]
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $schemaService = new SchemaService(new SchemaUpdate());
        $schemaUpdateResultRenderer = new SchemaUpdateResultRenderer();

        $schemaUpdateTypes = explode(',', $input->getArgument('schemaUpdateTypes'));
        $dryRun = $input->getOption('dry-run');
        $raw = $input->getOption('raw');
        $verbose = $output->isVerbose();

        /** @deprecated */
        $consoleOutput = new ConsoleOutput($output, $input);

        try {
            $expandedSchemaUpdateTypes = SchemaUpdateType::expandSchemaUpdateTypes($schemaUpdateTypes);
        } catch (InvalidEnumerationValueException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return 1;
        }

        $result = $schemaService->updateSchema($expandedSchemaUpdateTypes, $dryRun);

        if ($raw) {
            foreach ($result->getPerformedUpdates() as $updatesTypes) {
                foreach ($updatesTypes as $updates) {
                    $output->writeln($updates . ';' . PHP_EOL);
                }
            }

            return 0;
        }

        if ($result->hasPerformedUpdates()) {
            $output->writeln(sprintf(
                '<info>The following database schema updates %s performed:</info>',
                $dryRun ? 'should be' : 'were'
            ));
            $schemaUpdateResultRenderer->render($result, $consoleOutput, $verbose);
        } else {
            $output->writeln(sprintf(
                '<info>No schema updates %s performed for update type%s:%s</info>',
                $dryRun ? 'must be' : 'were',
                count($expandedSchemaUpdateTypes) > 1 ? 's' : '',
                PHP_EOL . '"' . implode('", "', $expandedSchemaUpdateTypes) . '"'
            ));
        }

        if ($result->hasErrors()) {
            $output->writeln('');
            $output->writeln('<error>The following errors occurred:</error>');
            $schemaUpdateResultRenderer->renderErrors($result, $consoleOutput, true);

            return 1;
        }

        return 0;
    }
}
