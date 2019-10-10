<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command\Frontend;

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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpProcess;

class FrontendRequestCommand extends AbstractConvertedCommand
{
    protected function configure()
    {
        $this->setDescription('Submit frontend request');
        $this->setHelp(
            <<<'EOH'
Submits a frontend request to TYPO3 on the specified URL.
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
            new InputArgument(
                'requestUrl',
                InputArgument::REQUIRED,
                'URL to make a frontend request'
            ),
        ];
    }

    /**
     * @deprecated will be removed with 6.0
     */
    protected function handleDeprecatedArgumentsAndOptions(InputInterface $input, OutputInterface $output)
    {
        // nothing to do here
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $requestUrl = $input->getArgument('requestUrl');

        // TODO: this needs heavy cleanup!
        $template = file_get_contents(__DIR__ . '/../../../../Resources/Private/Templates/request.tpl');
        $arguments = [
            'documentRoot' => getenv('TYPO3_PATH_WEB') ?: PATH_site,
            'requestUrl' => $this->makeAbsolute($requestUrl),
        ];

        // No other solution atm than to fake a CLI request type
        $code = str_replace('{arguments}', var_export($arguments, true), $template);
        $process = new PhpProcess($code, null, null, 0);
        $process->mustRun();
        $rawResponse = json_decode($process->getOutput());

        if ($rawResponse === null || $rawResponse->status === 'failure') {
            $output->writeln('<error>An error occurred while trying to request the specified URL.</error>');
            $output->writeln(sprintf('<error>Error: %s</error>', !empty($rawResponse->error) ? $rawResponse->error : 'Could not decode response. Please check your error log!'));
            $output->writeln(sprintf('<error>Content: %s</error>', $process->getOutput()));

            return 1;
        }

        $output->write($rawResponse->content, false, OutputInterface::OUTPUT_RAW);

        return 0;
    }

    /**
     * Make URL absolute, so that the core fake frontend request bootstrap
     * correctly configures the environment for trusted host pattern.
     *
     * @param string $url
     * @return string
     */
    protected function makeAbsolute($url)
    {
        $parsedUrl = parse_url($url);

        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : 'http';
        $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : 'localhost';
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';
        $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';

        return $scheme . '://' . $host . '/' . ltrim($path, '/') . $query;
    }
}
