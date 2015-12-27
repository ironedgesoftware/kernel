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

class ConfigProcessor2 implements ProcessorInterface
{
    public static $onComponentConfigRegistrationCalled = false;
    public static $onAfterProcessCalled = false;

    public function onComponentConfigRegistration(
        Kernel $kernel,
        ConfigInterface $config,
        $sourceComponentName,
        $targetComponentName,
        array $registeredConfig
    ) {
        self::$onComponentConfigRegistrationCalled = true;
    }

    public function onAfterProcess(Kernel $kernel, ConfigInterface $config)
    {
        self::$onAfterProcessCalled = true;
    }
}