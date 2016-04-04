<?php
namespace Helhum\Typo3Console\Command;

/*
 * This file is part of the typo3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

use Helhum\Typo3Console\Mvc\Controller\CommandController;
use Symfony\Component\Process\PhpProcess;
use TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\Response;

/**
 * Class SchedulerCommandController
 */
class FrontendCommandController extends CommandController
{
    /**
     * Submit a frontend request
     *
     * @param string $requestUrl URL to make a frontend request
     */
    public function requestCommand($requestUrl)
    {

        // TODO: this needs heavy cleanup!
        $template = file_get_contents(PATH_typo3 . 'sysext/core/Tests/Functional/Fixtures/Frontend/request.tpl');
        $arguments = array(
            'documentRoot' => PATH_site,
            'requestUrl' => $requestUrl,
        );
        // No other solution atm than to fake a CLI request type
        $code = '<?php
		define(\'TYPO3_REQUESTTYPE\', 6);
		?>';
        $code .= str_replace(array('{originalRoot}', '{arguments}'), array(PATH_site, var_export($arguments, true)), $template);
        $process = new PhpProcess($code);
        $process->mustRun();
        $rawResponse = json_decode($process->getOutput());
        if ($rawResponse === null || $rawResponse->status === Response::STATUS_Failure) {
            $this->outputLine('<error>An error occurred while trying to request the specified URL.</error>');
            $this->outputLine(sprintf('<error>Error: %s</error>', !empty($rawResponse->error) ? $rawResponse->error : 'Could not decode response. Please check your error log!'));
            $this->outputLine(sprintf('<error>Content: %s</error>', $process->getOutput()));
            $this->sendAndExit(1);
        }

        $this->output($rawResponse->content);
    }
}
