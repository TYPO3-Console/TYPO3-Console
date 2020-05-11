<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Tests\Functional\Fixtures\Extensions\ext_command\src;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Console\CommandRegistry;

class ExtCommand extends Command
{
    /**
     * @var CommandRegistry
     */
    private $registry;

    public function __construct(string $name = null, CommandRegistry $registry = null)
    {
        $this->registry = $registry;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('A test command');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->registry instanceof CommandRegistry ? 'injected' : 'no deps');

        if (isset($GLOBALS['TCA'])) {
            $output->writeln('full RunLevel');

            return 0;
        }
        $output->writeln('compile RunLevel');

        return 0;
    }

    public function isEnabled()
    {
        return !empty(getenv('TYPO3_CONSOLE_TEST_RUN'));
    }
}
