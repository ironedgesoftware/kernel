<?php

/*
 * This file is part of the frenzy-framework package.
 *
 * (c) Gustavo Falco <comfortablynumb84@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IronEdge\Component\Kernel\Config;


/*
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 */
use IronEdge\Component\Config\ConfigInterface;
use IronEdge\Component\Kernel\Kernel;

class Processor implements ProcessorInterface
{
    public function onComponentConfigRegistration(
        Kernel $kernel,
        ConfigInterface $config,
        $sourceComponentName,
        $targetComponentName,
        array $registeredConfig
    ) {
        if (isset($registeredConfig['cache'])) {
            if (isset($registeredConfig['cache']['instances'])) {

            }
        }
    }

    public function onAfterCache(Kernel $kernel, ConfigInterface $config)
    {
        // TODO: Implement onAfterCache() method.
    }

    public function onBeforeCache(Kernel $kernel, ConfigInterface $config)
    {
        // TODO: Implement onBeforeCache() method.
    }


}