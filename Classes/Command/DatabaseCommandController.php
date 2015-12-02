<?php
namespace Helhum\Typo3Console\Command;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Mathias Brodala <mbrodala@pagemachine.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Helhum\Typo3Console\Service\Database\Schema\SchemaUpdateException;
use Helhum\Typo3Console\Service\Database\Schema\SchemaUpdateResult;
use Helhum\Typo3Console\Service\Database\Schema\SchemaUpdateType;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;

/**
 * Database command controller
 */
class DatabaseCommandController extends CommandController {

	/**
	 * @var \Helhum\Typo3Console\Service\Database\Schema\SchemaService
	 * @inject
	 */
	protected $schemaService;

    /**
     * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
     * @inject
     */
    protected $configurationManager;

	/**
	 * Mapping of schema update types to human-readable labels
	 *
	 * @var array
	 */
	protected $schemaUpdateTypeLabels = array(
		SchemaUpdateType::FIELD_ADD => 'Add fields',
		SchemaUpdateType::FIELD_CHANGE => 'Change fields',
		SchemaUpdateType::FIELD_DROP => 'Drop fields',
		SchemaUpdateType::TABLE_ADD => 'Add tables',
		SchemaUpdateType::TABLE_CHANGE => 'Change tables',
		SchemaUpdateType::TABLE_CLEAR => 'Clear tables',
		SchemaUpdateType::TABLE_DROP => 'Drop tables',
	);

    /**
     * @var string
     */
    private $dumpDirectory;

    /**
     * @var string
     */
    private $dumpFilename;

    /**
     * @var array
     */
    private $databaseConnection;

    /**
     * @var string
     */
    private $mysqldumpCommandLine;

    /**
	 * Update database schema
	 *
	 * See Helhum\Typo3Console\Service\Database\Schema\SchemaUpdateType for a list of valid schema update types.
	 *
	 * The list of schema update types supports wildcards to specify multiple types, e.g.:
	 *
	 * "*" (all updates)
	 * "field.*" (all field updates)
	 * "*.add,*.change" (all add/change updates)
	 *
	 * To avoid shell matching all types with wildcards should be quoted.
	 *
	 * @param array $schemaUpdateTypes List of schema update types
	 */
	public function updateSchemaCommand(array $schemaUpdateTypes) {
		try {
			$schemaUpdateTypes = $this->expandSchemaUpdateTypes($schemaUpdateTypes);
		} catch (\UnexpectedValueException $e) {
			$this->outputLine(sprintf('<error>%s</error>', $e->getMessage()));
			$this->sendAndExit(1);
		}

		$result = $this->schemaService->updateSchema($schemaUpdateTypes);

		if ($result->hasPerformedUpdates()) {
			$this->output->outputLine('<info>The following schema updates where performed:</info>');
			$this->outputSchemaUpdateResult($result);
		} else {
			$this->output->outputLine('No schema updates matching the given types where performed');
		}
	}

	/**
	 * Expands wildcards in schema update types, e.g. field.* or *.change
	 *
	 * @param array $schemaUpdateTypes List of schema update types
	 * @return SchemaUpdateType[]
	 * @throws \UnexpectedValueException If an invalid schema update type was passed
	 */
	protected function expandSchemaUpdateTypes(array $schemaUpdateTypes) {
		$expandedSchemaUpdateTypes = array();
		$schemaUpdateTypeConstants = array_values(SchemaUpdateType::getConstants());

		// Collect total list of types by expanding wildcards
		foreach ($schemaUpdateTypes as $schemaUpdateType) {
			if (strpos($schemaUpdateType, '*') !== FALSE) {
				$matchPattern = '/' . str_replace('\\*', '.+', preg_quote($schemaUpdateType, '/')) . '/';
				$matchingSchemaUpdateTypes = preg_grep($matchPattern, $schemaUpdateTypeConstants);
				$expandedSchemaUpdateTypes = array_merge($expandedSchemaUpdateTypes, $matchingSchemaUpdateTypes);
			} else {
				$expandedSchemaUpdateTypes[] = $schemaUpdateType;
			}
		}

		// Cast to enumeration objects to ensure valid values
		foreach ($expandedSchemaUpdateTypes as &$schemaUpdateType) {
			try {
				$schemaUpdateType = SchemaUpdateType::cast($schemaUpdateType);
			} catch (InvalidEnumerationValueException $e) {
				throw new \UnexpectedValueException(sprintf(
					'Invalid schema update type "%s", must be one of: "%s"',
					$schemaUpdateType,
					implode('", "', $schemaUpdateTypeConstants)
				), 1439460396);
			}
		}

		return $expandedSchemaUpdateTypes;
	}

	/**
	 * Renders a table for a schema update result
	 *
	 * @param SchemaUpdateResult $result Result of the schema update
	 * @return void
	 */
	protected function outputSchemaUpdateResult(SchemaUpdateResult $result) {
		$tableRows = array();

		foreach ($result->getPerformedUpdates() as $type => $numberOfUpdates) {
			$tableRows[] = array($this->schemaUpdateTypeLabels[(string)$type], $numberOfUpdates);
		}

		$this->output->outputTable($tableRows, array('Type', 'Updates'));

		if ($result->hasErrors()) {
			foreach ($result->getErrors() as $type => $errors) {
				$this->output->outputLine(sprintf('<error>Errors during "%s" schema update:</error>', $this->schemaUpdateTypeLabels[(string)$type]));

				foreach ($errors as $error) {
					$this->output->outputFormatted('<error>' . $error . '</error>', array(), 2);
				}
			}
		}
	}

    /**
     * Backup the Typo3 database.
     *
     * Dumps the Typo3 database to a local directory.
     * Command makes use of mysqldump. Therefore mysqldump is a dependency.
     * Make sure that mysqldump is within your $PATH.
     *
     * @param string $dumpDirectory Directory to put the dump into. E.g. /tmp
     * @throws \UnexpectedValueException
     */
    public function backupCommand($dumpDirectory) {
        try {
            $this->setDumpDirectory($dumpDirectory);
        } catch (\UnexpectedValueException $e) {
            $this->outputLine(sprintf('<error>%s</error>', $e->getMessage()));
            $this->sendAndExit(1);
        }
        $this->setDumpFilename();
        $this->setMysqldumpCommandLine();
        $this->output->outputLine($this->mysqldumpCommandLine);

        $this->process($this->mysqldumpCommandLine);
    }

    /**
     * Calls 'system()' function, passing through all arguments unchanged.
     *
     * @param string $exec The shell command to execute.  Parameters should already be escaped.
     * @return int The result code from system():  0 == success.
     */
    private function process($exec) {
        $this->output->outputLine("Calling system($exec);");
        system($exec, $result_code);

        return $result_code;
    }

    /**
     * Builds and sets the mysqldump command line.
     *
     * @todo Check whether if it is a socket mysql connection.
     */
    private function setMysqldumpCommandLine() {
        if (!isset($this->databaseConnection)) {
            $this->setDatabaseConnection();
        }

        $exec = 'mysqldump';
        $exec .=
            sprintf(
                ' --user=%s --password=%s --host=%s %s',
                $this->databaseConnection['username'],
                $this->databaseConnection['password'],
                $this->databaseConnection['host'],
                $this->databaseConnection['database']
            );

        $exec .= ' --no-autocommit --single-transaction --opt -Q';
        $exec .= ' --skip-extended-insert --order-by-primary';
        $exec .= ' > ' . $this->dumpDirectory . '/' . $this->dumpFilename;

        $this->mysqldumpCommandLine = $exec;
    }

    /**
     * Sets the database connection of the current Typo3 instance.
     */
    private function setDatabaseConnection() {
        $this->databaseConnection = $this->configurationManager->getConfigurationValueByPath('DB');
    }

    /**
     * Sets the filename for the database dump.
     */
    private function setDumpFilename() {
        if (!isset($this->databaseConnection)) {
            $this->setDatabaseConnection();
        }

        $this->dumpFilename = sprintf(
            'dump-%s.sql',
            $this->databaseConnection['database']
        );
    }

    /**
     * Sets the directory to put the dump into.
     *
     * @param string $dumpDirectory
     */
    private function setDumpDirectory($dumpDirectory) {
        $dumpDirectory = rtrim($dumpDirectory, '/');
        if ($this->checkIfDirectoryExists($dumpDirectory) && $this->checkIfDirectoryIsWritable($dumpDirectory)) {
            $this->dumpDirectory = $dumpDirectory;
        }
    }

    /**
     * Checks if a file exists and if it is a directory.
     *
     * @param string $filename
     * @return bool
     * @throws \UnexpectedValueException
     */
    private function checkIfDirectoryExists($filename) {
        if (file_exists($filename) && is_dir($filename)) {
            return true;
        } else {
            throw new \UnexpectedValueException(sprintf(
                'Directory "%s" does not exist.',
                $filename
            ));
        }
    }

    /**
     * Checks if a directory is writable.
     *
     * @param $filename
     * @return bool
     * @throws \UnexpectedValueException
     */
    private function checkIfDirectoryIsWritable($filename) {
        if (is_writable($filename)) {
            return true;
        } else {
            throw new \UnexpectedValueException(sprintf(
                'Directory "%s" not writable.',
                $filename
            ));
        }
    }
}
