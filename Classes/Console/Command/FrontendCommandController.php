<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Command;

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

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Symfony\Component\Process\PhpProcess;

class FrontendCommandController extends CommandController
{
    /**
     * Submit frontend request
     *
     * Submits a frontend request to TYPO3 on the specified URL.
     *
     * @param string $requestUrl URL to make a frontend request.
     */
    public function requestCommand($requestUrl)
    {
        // TODO: this needs heavy cleanup!
        $template = file_get_contents(__DIR__ . '/../../../Resources/Private/Templates/request.tpl');
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
            $this->outputLine('<error>An error occurred while trying to request the specified URL.</error>');
            $this->outputLine(sprintf('<error>Error: %s</error>', !empty($rawResponse->error) ? $rawResponse->error : 'Could not decode response. Please check your error log!'));
            $this->outputLine(sprintf('<error>Content: %s</error>', $process->getOutput()));
            $this->quit(1);
        }

        $this->output($rawResponse->content);
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
