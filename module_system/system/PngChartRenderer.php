<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

use Symfony\Component\Process\Process;

/**
 * Chart renderer using node for backend converting of charts into images for later use on pdf creation
 *
 * @package module_system
 * @author bernhard.grabietz@artemeon.de
 * @since 7.1
 */
class PngChartRenderer implements ChartRendererInterface
{
    /**
     * @var string[]
     */
    private $command;

    /**
     * PngChartRenderer constructor.
     * @param string[] $command
     */
    public function __construct(array $command)
    {
        $this->command = array_merge($command, [_realpath_ . 'bin/node/renderGraphImage.js']);
    }

    /**
     * render given graph into image using node
     * @param GraphInterface $chart
     * @param string $outputPath
     * @return string
     */
    public function render(GraphInterface $chart, string $outputPath = _realpath_ . 'files/rendered-graphs'): string
    {
        $process = new Process($this->command);
        $input = [
            'chart' => $chart,
            'outputPath' => $outputPath
        ];

        $process->setInput(json_encode($input));
        $process->run();

        // todo some evaluation of $process->getOutput() to handle possible thrown exceptions from render javascript
        return $process->getOutput();
    }
}
