<?php
declare(strict_types=1);
namespace Typo3Console\CreateReferenceCommand;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use Typo3Console\CreateReferenceCommand\Command\CommandReferenceRenderCommand;

class ServiceProvider extends AbstractServiceProvider
{
    protected static function getPackagePath(): string
    {
        return __DIR__ . '/../';
    }

    protected static function getPackageName(): string
    {
        return 'typo3-console/create-reference-command';
    }

    public function getFactories(): array
    {
        return [
            CommandReferenceRenderCommand::class => [ static::class, 'getCommandReferenceRenderCommand' ],
        ];
    }

    public function getExtensions(): array
    {
        return [
            CommandRegistry::class => [ static::class, 'configureCommands' ],
        ] + parent::getExtensions();
    }

    public static function getCommandReferenceRenderCommand(ContainerInterface $container): CommandReferenceRenderCommand
    {
        return new CommandReferenceRenderCommand(
            $container,
            $container->get(BootService::class)
        );
    }

    public static function configureCommands(ContainerInterface $container, CommandRegistry $commandRegistry): CommandRegistry
    {
        $commandRegistry->addLazyCommand('commandreference:render', CommandReferenceRenderCommand::class, 'Renders command reference documentation from source code');

        return $commandRegistry;
    }
}
