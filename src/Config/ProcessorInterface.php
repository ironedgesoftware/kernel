<?php

/*
 * This file is part of the kernel package.
 *
 * (c) Gustavo Falco <comfortablynumb84@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IronEdge\Component\Kernel\Config;


use IronEdge\Component\Config\ConfigInterface;
use IronEdge\Component\Kernel\Kernel;

/*
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 */
interface ProcessorInterface
{
    /**
     * This method is called when a component wants to register some configuration data to other component.
     *
     * @param Kernel          $kernel - Kernel.
     * @param ConfigInterface $config - Config object.
     * @param string          $sourceComponentName - Source component name.
     * @param string          $targetComponentName - Target component name.
     * @param array           $registeredConfig    - Configuration registered by the source component.
     *
     * @return void
     */
    public function onComponentConfigRegistration(
        Kernel $kernel,
        ConfigInterface $config,
        $sourceComponentName,
        $targetComponentName,
        array $registeredConfig
    );

    /**
     * This method is called BEFORE caching the configuration. This method should be used if you need
     * to validate and / or modify the configuration in any way before we cache the final array.
     *
     * @param Kernel          $kernel - Kernel.
     * @param ConfigInterface $config - Config object.
     *
     * @return void
     */
    public function onBeforeCache(Kernel $kernel, ConfigInterface $config);

    /**
     * This method is called AFTER caching the configuration. This method should be used
     * when you need to manually create services / cache instances / etc.
     *
     * @param Kernel          $kernel - Kernel.
     * @param ConfigInterface $config - Config object.
     *
     * @return void
     */
    public function onAfterCache(Kernel $kernel, ConfigInterface $config);
}