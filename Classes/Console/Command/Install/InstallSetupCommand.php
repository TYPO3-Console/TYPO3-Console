<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Install;

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

use Helhum\Typo3Console\Install\Action\InstallActionDispatcher;
use Helhum\Typo3Console\Mvc\Cli\ConsoleOutput;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Input\ArgvInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;

class InstallSetupCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('TYPO3 Setup');
        $this->setHelp(
            <<<'EOH'
Use as command line replacement for the web installation process.
Manually enter details on the command line or non interactive for automated setups.
As an alternative for providing command line arguments, it is also possible to provide environment variables.
Command line arguments take precedence over environment variables.
The following environment variables are evaluated:

- TYPO3_INSTALL_DB_DRIVER
- TYPO3_INSTALL_DB_USER
- TYPO3_INSTALL_DB_PASSWORD
- TYPO3_INSTALL_DB_HOST
- TYPO3_INSTALL_DB_PORT
- TYPO3_INSTALL_DB_UNIX_SOCKET
- TYPO3_INSTALL_DB_USE_EXISTING
- TYPO3_INSTALL_DB_DBNAME
- TYPO3_INSTALL_ADMIN_USER
- TYPO3_INSTALL_ADMIN_PASSWORD
- TYPO3_INSTALL_SITE_NAME
- TYPO3_INSTALL_SITE_SETUP_TYPE
- TYPO3_INSTALL_SITE_BASE_URL
- TYPO3_INSTALL_WEB_SERVER_CONFIG
EOH
        );
        $this->setDefinition([
            new InputOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force installation of TYPO3, even if `LocalConfiguration.php` file already exists.'
            ),
            new InputOption(
                'skip-integrity-check',
                null,
                InputOption::VALUE_NONE,
                'Skip the checking for clean state before executing setup. This allows a pre-defined `LocalConfiguration.php` to be present. Handle with care. It might lead to unexpected or broken installation results.'
            ),
            new InputOption(
                'skip-extension-setup',
                null,
                InputOption::VALUE_NONE,
                'Skip setting up extensions after TYPO3 is set up. Defaults to false in composer setups and to true in non composer setups.'
            ),
            new InputOption(
                'install-steps-config',
                null,
                InputOption::VALUE_REQUIRED,
                'Override install steps with the ones given in this file'
            ),
            new InputOption(
                'database-driver',
                null,
                InputOption::VALUE_REQUIRED,
                'Database connection type (one of mysqli, pdo_sqlite, pdo_mysql, pdo_pgsql, mssql) Note: pdo_sqlite is only supported with TYPO3 9.5 or higher',
                'mysqli'
            ),
            new InputOption(
                'database-user-name',
                null,
                InputOption::VALUE_REQUIRED,
                'User name for database server',
                ''
            ),
            new InputOption(
                'database-user-password',
                null,
                InputOption::VALUE_REQUIRED,
                'User password for database server',
                ''
            ),
            new InputOption(
                'database-host-name',
                null,
                InputOption::VALUE_REQUIRED,
                'Host name of database server',
                '127.0.0.1'
            ),
            new InputOption(
                'database-port',
                null,
                InputOption::VALUE_REQUIRED,
                'TCP Port of database server',
                '3306'
            ),
            new InputOption(
                'database-socket',
                null,
                InputOption::VALUE_REQUIRED,
                'Unix Socket to connect to (if localhost is given as hostname and this is kept empty, a socket connection will be established)',
                ''
            ),
            new InputOption(
                'database-name',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the database'
            ),
            new InputOption(
                'use-existing-database',
                null,
                InputOption::VALUE_NONE,
                'If set an empty database with the specified name will be used. Otherwise a database with the specified name is created.'
            ),
            new InputOption(
                'admin-user-name',
                null,
                InputOption::VALUE_REQUIRED,
                'User name of the administrative backend user account to be created'
            ),
            new InputOption(
                'admin-password',
                null,
                InputOption::VALUE_REQUIRED,
                'Password of the administrative backend user account to be created'
            ),
            new InputOption(
                'site-name',
                null,
                InputOption::VALUE_REQUIRED,
                'Site Name',
                'New TYPO3 Console site'
            ),
            new InputOption(
                'web-server-config',
                null,
                InputOption::VALUE_REQUIRED,
                'Web server config file to install in document root (`none`, `apache`, `iis`)',
                'none'
            ),
            new InputOption(
                'site-setup-type',
                null,
                InputOption::VALUE_REQUIRED,
                'Can be either `no` (which unsurprisingly does nothing at all) or `site` (which creates an empty root page and setup)',
                'no'
            ),
            new InputOption(
                'site-base-url',
                null,
                InputOption::VALUE_REQUIRED,
                'When `site-setup-type` is set to `site`, this base url is used for the created site configuration',
                '/'
            ),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');
        $skipIntegrityCheck = $input->getOption('skip-integrity-check');
        $skipExtensionSetup = $input->getOption('skip-extension-setup');
        $installStepsConfig = $input->getOption('install-steps-config');
        $isInteractive = $input->isInteractive();

        $output->writeln('');
        $output->writeln('<i>Welcome to the TYPO3 Console installer!</i>');
        $output->writeln('');

        // @deprecated ConsoleOutput should be removed in 6.0
        $consoleOutput = new ConsoleOutput($output, $input);
        $installActionDispatcher = new InstallActionDispatcher($consoleOutput);
        $installationSucceeded = $installActionDispatcher->dispatch(
            $this->getNormalizedInstallStepArguments($input),
            [
                'integrityCheck' => !$skipIntegrityCheck,
                'forceInstall' => $force,
                'interactive' => $isInteractive,
                'extensionSetup' => !$skipExtensionSetup && Environment::isComposerMode(),
            ],
            $installStepsConfig
        );

        if (!$installationSucceeded) {
            return 2;
        }

        $output->writeln('');
        $output->writeln('<i>Successfully installed TYPO3 CMS!</i>');

        return 0;
    }

    private function getNormalizedInstallStepArguments(InputInterface $input): array
    {
        $normalizedArguments = [];
        foreach ($input->getOptions() as $optionName => $value) {
            if ($value !== null && $input instanceof ArgvInput && $input->hasGivenOption($optionName)) {
                $normalizedArguments[$this->dashedToLowerCamelCase($optionName)] = $value;
            }
        }

        return $normalizedArguments;
    }

    private function dashedToLowerCamelCase(string $string): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', strtolower($string)))));
    }
}
