<?php

/*
 * This file is part of the frenzy-framework package.
 *
 * (c) Gustavo Falco <comfortablynumb84@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IronEdge\Component\Kernel\Test\Helper;


/*
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 */
use IronEdge\Component\Config\ConfigInterface;
use IronEdge\Component\Kernel\Config\ProcessorInterface;
use IronEdge\Component\Kernel\Kernel;

class ConfigProcessor implements ProcessorInterface
{
    public function process(Kernel $kernel, ConfigInterface $config)
    {
        $config->set('processor_config_param', 'processor_config_value');
        $config->set('processor_config.custom_param_1', $config->get('custom_params.custom_param_3'));
    }

}