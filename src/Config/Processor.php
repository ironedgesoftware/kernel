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
use IronEdge\Component\Kernel\Exception\InvalidConfigException;
use IronEdge\Component\Kernel\Kernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

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

    public function onBeforeCache(Kernel $kernel, ConfigInterface $config)
    {

    }

    public function onAfterCache(Kernel $kernel, ConfigInterface $config)
    {

    }

    public function onBeforeContainerCompile(
        Kernel $kernel,
        ConfigInterface $config,
        ContainerBuilder $containerBuilder
    ) {
        // Register event listeners

        $eventListenerTags = $containerBuilder->findTaggedServiceIds('kernel.event_listener');
        $eventDispatcherDefinition = $containerBuilder->getDefinition('event_dispatcher');

        foreach ($eventListenerTags as $serviceId => $tags) {
            foreach ($tags as $tagData) {
                if (!isset($tagData['event']) || !isset($tagData['method'])) {
                    throw InvalidConfigException::create(
                        'Service ID "'.$serviceId.'" has a tag "kernel.event_listener" without mandatory parameters: '.
                        'event, method.'
                    );
                }

                if (isset($tagData['priority'])) {
                    if (!preg_match('/^[0-9]+$/', $tagData['priority'])) {
                        throw InvalidConfigException::create(
                            'Service ID "'.$serviceId.'" has a tag "kernel.event_listener" with an invalid "priority" '.
                            'value. It must be an integer. Current value: '.print_r($tagData['priority'], true)
                        );
                    }
                } else {
                    $tagData['priority'] = 0;
                }

                $eventDispatcherDefinition->addMethodCall(
                    'addListener',
                    [
                        $tagData['event'],
                        [
                            new Reference($serviceId),
                            $tagData['method']
                        ],
                        $tagData['priority']
                    ]
                );
            }
        }
    }


}