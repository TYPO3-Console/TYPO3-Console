<?php
declare(strict_types=1);
namespace Typo3Console\SQLCommand;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use Typo3Console\SQLCommand\Command\SqlCommand;

class ServiceProvider extends AbstractServiceProvider
{
    protected static function getPackagePath(): string
    {
        return __DIR__ . '/../';
    }

    protected static function getPackageName(): string
    {
        return 'typo3-console/sql-command';
    }

    public function getFactories(): array
    {
        return [
            SqlCommand::class => [ static::class, 'getSqlCommand' ],
        ];
    }

    public function getExtensions(): array
    {
        return [
            CommandRegistry::class => [ static::class, 'configureCommands' ],
        ] + parent::getExtensions();
    }

    public static function getSqlCommand(): SqlCommand
    {
        return new SqlCommand('sql');
    }

    public static function configureCommands(ContainerInterface $container, CommandRegistry $commandRegistry): CommandRegistry
    {
        $commandRegistry->addLazyCommand('sql', SqlCommand::class, '');

        return $commandRegistry;
    }
}
