<?php

/*
 * This file is part of the kernel package.
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
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigProcessor2 implements ProcessorInterface
{
    public static $onComponentConfigRegistrationCalled = false;
    public static $onBeforeCache = false;
    public static $onAfterCache = false;
    public static $onBeforeContainerCompile = false;

    public function onComponentConfigRegistration(
        Kernel $kernel,
        ConfigInterface $config,
        $sourceComponentName,
        $targetComponentName,
        array $registeredConfig
    ) {
        self::$onComponentConfigRegistrationCalled = true;
    }

    public function onBeforeCache(Kernel $kernel, ConfigInterface $config)
    {
        self::$onBeforeCache = true;
    }

    public function onAfterCache(Kernel $kernel, ConfigInterface $config)
    {
        self::$onAfterCache = true;
    }

    public function onBeforeContainerCompile(
        Kernel $kernel,
        ConfigInterface $config,
        ContainerBuilder $containerBuilder
    ) {
        self::$onBeforeContainerCompile = true;
    }
}