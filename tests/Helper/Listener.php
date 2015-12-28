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
use IronEdge\Component\Kernel\Event\ListenerInterface;
use IronEdge\Component\Kernel\Kernel;

class Listener implements ListenerInterface
{
    public static $onComponentConfigRegistrationCalled = false;
    public static $onAfterProcessCalled = false;

    private $_sourceComponent;

    private $_targetComponent;

    public function onComponentConfigRegistration(
        Kernel $kernel,
        ConfigInterface $config,
        $sourceComponentName,
        $targetComponentName,
        array $registeredConfig
    ) {
        self::$onComponentConfigRegistrationCalled = true;

        $this->_sourceComponent = $sourceComponentName;
        $this->_targetComponent = $targetComponentName;

        $config->set(
            'components.'.$targetComponentName.'.on_component_config_registration.source_component',
            $registeredConfig
        );
    }

    public function onAfterProcess(Kernel $kernel, ConfigInterface $config)
    {
        self::$onAfterProcessCalled = true;

        if ($this->_targetComponent) {
            $config->set(
                'components.'.$this->_targetComponent.'.on_after_process.source_component',
                $this->_sourceComponent
            );
        }
    }

}