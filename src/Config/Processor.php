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
use IronEdge\Component\Logger\Factory;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class Processor implements ProcessorInterface
{
    /**
     * Field _loggerInstancesServicesById.
     *
     * @var array
     */
    private $_loggerInstancesServicesById = [];

    /**
     * Field _loggerInstancesComponentById.
     *
     * @var array
     */
    private $_loggerInstancesComponentById = [];

    /**
     * Field _loggerHandlersServicesById.
     *
     * @var array
     */
    private $_loggerHandlersServicesById = [];

    /**
     * Field _loggerHandlersComponentById.
     *
     * @var array
     */
    private $_loggerHandlersComponentById = [];

    /**
     * Field _loggerProcessorsServicesById.
     *
     * @var array
     */
    private $_loggerProcessorsServicesById = [];

    /**
     * Field _loggerProcessorsComponentById.
     *
     * @var array
     */
    private $_loggerProcessorsComponentById = [];

    /**
     * Field _loggerFormattersServicesById.
     *
     * @var array
     */
    private $_loggerFormattersServicesById = [];

    /**
     * Field _loggerFormattersComponentById.
     *
     * @var array
     */
    private $_loggerFormattersComponentById = [];



    public function onComponentConfigRegistration(
        Kernel $kernel,
        ConfigInterface $config,
        $sourceComponentName,
        $targetComponentName,
        array $registeredConfig
    ) {
        if (isset($registeredConfig['logger'])) {
            if (!is_array($registeredConfig['logger'])) {
                throw InvalidConfigException::create('Parameter "logger" must be an array.');
            }

            if (isset($registeredConfig['logger']['formatters'])) {
                $this->registerLoggerFormatters($sourceComponentName, $registeredConfig['logger']['formatters']);
            }

            if (isset($registeredConfig['logger']['handlers'])) {
                $this->registerLoggerHandlers($sourceComponentName, $registeredConfig['logger']['handlers']);
            }

            if (isset($registeredConfig['logger']['processors'])) {
                $this->registerLoggerProcessors($sourceComponentName, $registeredConfig['logger']['processors']);
            }

            if (isset($registeredConfig['logger']['instances'])) {
                $this->registerLoggerInstances($sourceComponentName, $registeredConfig['logger']['instances']);
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

        // Create loggers

        foreach ($this->_loggerFormattersServicesById as $formatterId => $formatterDefinition) {
            if (is_string($formatterDefinition)) {
                continue;
            }

            $containerBuilder->setDefinition('logger.formatter.'.$formatterId, $formatterDefinition);
        }

        foreach ($this->_loggerHandlersServicesById as $handlerId => $handlerDefinition) {
            if (is_string($handlerDefinition)) {
                continue;
            }

            $containerBuilder->setDefinition('logger.handler.'.$handlerId, $handlerDefinition);
        }

        foreach ($this->_loggerProcessorsServicesById as $processorId => $processorDefinition) {
            if (is_string($processorDefinition)) {
                continue;
            }

            $containerBuilder->setDefinition('logger.processor.'.$processorId, $processorDefinition);
        }

        /** @var Definition $loggerDefinition */
        foreach ($this->_loggerInstancesServicesById as $loggerId => $loggerDefinition) {
            $containerBuilder->setDefinition('logger.'.$loggerId, $loggerDefinition);
        }
    }

    /**
     * Registers logger instances.
     *
     * @param string $sourceComponentName - Component who wants to register the instance.
     * @param array  $instances           - Instances.
     *
     * @throws InvalidConfigException
     * @throws \IronEdge\Component\Logger\Exception\InvalidConfigException
     *
     * @return void
     */
    protected function registerLoggerInstances($sourceComponentName, $instances)
    {
        if (!is_array($instances)) {
            throw InvalidConfigException::create('Parameter "logger.instances" must be an array.');
        }

        foreach ($instances as $instanceId => $instanceConfig) {
            if (!is_array($instanceConfig)) {
                throw InvalidConfigException::create(
                    'Parameter "logger.instances.'.$instanceId.'" must be an array.'
                );
            }

            if (isset($this->_loggerInstancesComponentById[$instanceId])) {
                throw InvalidConfigException::create(
                    'Component "'.$sourceComponentName.'" wants to register a logger handler "'.$instanceId.
                    '", but component "'.$this->_loggerInstancesComponentById[$instanceId].
                    '" already registered it.'
                );
            }

            $definition = new Definition();

            $definition->setFactory(
                [
                    new Reference('logger.factory'),
                    'createLogger'
                ]
            );
            $definition->setArguments(
                [
                    $instanceId,
                    []
                ]
            );
            $definition->setClass(
                '\Monolog\Logger'
            );

            if (isset($instanceConfig['handlers'])) {
                if (!is_array($instanceConfig['handlers'])) {
                    throw InvalidConfigException::create(
                        'Logger "'.$instanceId.'" must have a parameter "handlers" with an array.'
                    );
                }

                foreach ($instanceConfig['handlers'] as $handlerId) {
                    if (!isset($this->_loggerHandlersServicesById[$handlerId])) {
                        throw InvalidConfigException::create(
                            'Logger instance "'.$instanceId.'" has an unregistered handler ID "'.$handlerId.'".'
                        );
                    }

                    $definition->addMethodCall(
                        'pushHandler',
                        [
                            new Reference(
                                is_string($this->_loggerHandlersServicesById[$handlerId]) ?
                                    $this->_loggerHandlersServicesById[$handlerId] :
                                    'logger.handler.'.$handlerId
                            )
                        ]
                    );
                }
            }

            if (isset($instanceConfig['processors'])) {
                if (!is_array($instanceConfig['processors'])) {
                    throw InvalidConfigException::create(
                        'Logger "'.$instanceId.'" must have a parameter "processors" with an array.'
                    );
                }

                foreach ($instanceConfig['processors'] as $processorId) {
                    if (!isset($this->_loggerProcessorsServicesById[$processorId])) {
                        throw InvalidConfigException::create(
                            'Logger instance "'.$instanceId.'" has an unregistered processor ID "'.$processorId.'".'
                        );
                    }

                    $definition->addMethodCall(
                        'pushProcessor',
                        [
                            new Reference(
                                is_string($this->_loggerProcessorsServicesById[$processorId]) ?
                                    $this->_loggerProcessorsServicesById[$processorId] :
                                    'logger.processor.'.$processorId
                            )
                        ]
                    );
                }
            }

            $this->_loggerInstancesServicesById[$instanceId] = $definition;
            $this->_loggerInstancesComponentById[$instanceId] = $sourceComponentName;
        }
    }

    /**
     * Registers logger handlers.
     *
     * @param string $sourceComponentName - Component who wants to register the handlers.
     * @param array  $handlers            - Handlers.
     *
     * @throws InvalidConfigException
     * @throws \IronEdge\Component\Logger\Exception\InvalidConfigException
     *
     * @return void
     */
    protected function registerLoggerHandlers($sourceComponentName, $handlers)
    {
        if (!is_array($handlers)) {
            throw InvalidConfigException::create('Parameter "logger.handlers" must be an array.');
        }

        foreach ($handlers as $handlerId => $handlerConfig) {
            if (!is_array($handlers[$handlerId])) {
                throw InvalidConfigException::create(
                    'Parameter "logger.handlers.'.$handlerId.'" must be an array.'
                );
            }

            if (isset($this->_loggerHandlersComponentById[$handlerId])) {
                throw InvalidConfigException::create(
                    'Component "'.$sourceComponentName.'" wants to register a logger handler "'.$handlerId.
                    '", but component "'.$this->_loggerHandlersComponentById[$handlerId].
                    '" already registered it.'
                );
            }

            if (isset($handlerConfig['serviceId'])) {
                if (!is_string($handlerConfig['serviceId'])) {
                    throw InvalidConfigException::create(
                        'A logger handler with ID "'.$handlerId.'" must have its "serviceId" parameter with a '.
                        'string value.'
                    );
                }

                $this->_loggerHandlersServicesById[$handlerId] = $handlerConfig['serviceId'];
            } else {
                $level = isset($handlerConfig['level']) ?
                    (int)constant('\Monolog\Logger::' . $handlerConfig['level']) :
                    Logger::DEBUG;
                $type = isset($handlerConfig['type']) ?
                    $handlerConfig['type'] :
                    'stream';
                $formatterId = isset($handlerConfig['formatterId']) ?
                    $handlerConfig['formatterId'] :
                    'custom_line_formatter';

                if ($level === null) {
                    throw InvalidConfigException::create(
                        'Handler with ID "' . $handlerId . '" registered an invalid level "' .
                        $handlerConfig['level'] . '".'
                    );
                }

                $definition = new Definition();

                $definition->setFactory(
                    [
                        new Reference('logger.factory'),
                        'createHandler'
                    ]
                );
                $definition->setArguments(
                    [
                        $handlerId,
                        $type,
                        $level,
                        $handlerConfig
                    ]
                );
                $definition->setClass(
                    '\Monolog\Handler\HandlerInterface'
                );
                $definition->addMethodCall('setFormatter', [new Reference('logger.formatter.' . $formatterId)]);

                $this->_loggerHandlersServicesById[$handlerId] = $definition;
            }

            $this->_loggerHandlersComponentById[$handlerId] = $sourceComponentName;
        }
    }

    /**
     * Registers logger processors.
     *
     * @param string $sourceComponentName - Component who wants to register the processors.
     * @param array  $processors          - Processors.
     *
     * @throws InvalidConfigException
     * @throws \IronEdge\Component\Logger\Exception\InvalidConfigException
     *
     * @return void
     */
    protected function registerLoggerProcessors($sourceComponentName, $processors)
    {
        if (!is_array($processors)) {
            throw InvalidConfigException::create('Parameter "logger.processors" must be an array.');
        }

        foreach ($processors as $processorId => $processorConfig) {
            if (!is_array($processorConfig)) {
                throw InvalidConfigException::create(
                    'Parameter "logger.processors.'.$processorId.'" must be an array.'
                );
            }

            if (isset($this->_loggerProcessorsComponentById[$processorId])) {
                throw InvalidConfigException::create(
                    'Component "'.$sourceComponentName.'" wants to register a logger processor "'.$processorId.
                    '", but component "'.$this->_loggerProcessorsComponentById[$processorId].
                    '" already registered it.'
                );
            }

            if (isset($processorConfig['serviceId'])) {
                if (!is_string($processorConfig['serviceId'])) {
                    throw InvalidConfigException::create(
                        'A logger processor with ID "'.$processorId.'" must have its "serviceId" parameter with a '.
                        'string value.'
                    );
                }

                $this->_loggerProcessorsServicesById[$processorId] = $processorConfig['serviceId'];
            } else {
                if (!isset($processorConfig['type'])) {
                    throw InvalidConfigException::create('Processor "'.$processorId.'" must set the "type" parameter.');
                }

                $definition = new Definition();

                $definition->setFactory(
                    [
                        new Reference('logger.factory'),
                        'createProcessor'
                    ]
                );
                $definition->setArguments(
                    [
                        $processorId,
                        $processorConfig['type'],
                        $processorConfig
                    ]
                );

                $this->_loggerProcessorsServicesById[$processorId] = $definition;
            }

            $this->_loggerProcessorsComponentById[$processorId] = $sourceComponentName;
        }
    }

    /**
     * Registers logger formatter.
     *
     * @param string $sourceComponentName - Component who wants to register the formatters.
     * @param array  $formatters          - Formatters.
     *
     * @throws InvalidConfigException
     * @throws \IronEdge\Component\Logger\Exception\InvalidConfigException
     *
     * @return void
     */
    protected function registerLoggerFormatters($sourceComponentName, $formatters)
    {
        if (!is_array($formatters)) {
            throw InvalidConfigException::create('Parameter "logger.formatters" must be an array.');
        }

        foreach ($formatters as $formatterId => $formatterConfig) {
            if (!is_array($formatterConfig)) {
                throw InvalidConfigException::create(
                    'Parameter "logger.formatters.'.$formatterId.'" must be an array.'
                );
            }

            if (isset($this->_loggerFormattersComponentById[$formatterId])) {
                throw InvalidConfigException::create(
                    'Component "'.$sourceComponentName.'" wants to register a logger formatter "'.$formatterId.
                    '", but component "'.$this->_loggerFormattersComponentById[$formatterId].
                    '" already registered it.'
                );
            }

            if (isset($formatterConfig['serviceId'])) {
                if (!is_string($formatterConfig['serviceId'])) {
                    throw InvalidConfigException::create(
                        'A logger formatter with ID "'.$formatterConfig.'" must have its "serviceId" parameter with a '.
                        'string value.'
                    );
                }

                $this->_loggerFormattersServicesById[$formatterId] = $formatterConfig['serviceId'];
            } else {
                if (!isset($formatterConfig['type'])) {
                    throw InvalidConfigException::create('Formatter "'.$formatterId.'" must set the "type" parameter.');
                }

                $definition = new Definition();

                $definition->setFactory(
                    [
                        new Reference('logger.factory'),
                        'createFormatter'
                    ]
                );
                $definition->setArguments(
                    [
                        $formatterId,
                        $formatterConfig['type'],
                        $formatterConfig
                    ]
                );
                $definition->setClass('\Monolog\Formatter\FormatterInterface');

                $this->_loggerFormattersServicesById[$formatterId] = $definition;
            }

            $this->_loggerFormattersComponentById[$formatterId] = $sourceComponentName;
        }
    }
}