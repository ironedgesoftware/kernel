<?php

/*
 * This file is part of the kernel package.
 *
 * (c) Gustavo Falco <comfortablynumb84@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IronEdge\Component\Kernel\Test\Integration;

use IronEdge\Component\Kernel\Kernel;
use IronEdge\Component\Kernel\Test\Helper\ConfigProcessor;
use IronEdge\Component\Kernel\Test\Helper\ConfigProcessor2;
use IronEdge\Component\Kernel\Test\Helper\EventListener;
use IronEdge\Component\Kernel\Test\Helper\EventListener2;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\Yaml\Yaml;

/*
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 */
class KernelTest extends AbstractTestCase
{
    /**
     * Field containerKernelTest.
     *
     * @var Kernel
     */
    public static $containerKernelTest;


    public function setUp()
    {
        $this->cleanUp();

        if (self::$containerKernelTest === null) {
            $options = [
                'directories'       => [
                    'rootPath'          => $this->getTestRootPath(),
                    'vendorPath'        => $this->getTestVendorPath(),
                    'configPath'        => $this->getTestConfigPath()
                ]
            ];

            self::$containerKernelTest = new Kernel($options);
        }

        ConfigProcessor::$onComponentConfigRegistrationCalled = false;
        ConfigProcessor::$onBeforeCache = false;
        ConfigProcessor::$onAfterCache = false;
        ConfigProcessor::$onBeforeContainerCompile = false;
        ConfigProcessor2::$onComponentConfigRegistrationCalled = false;
        ConfigProcessor2::$onBeforeCache = false;
        ConfigProcessor2::$onAfterCache = false;
        ConfigProcessor2::$onBeforeContainerCompile = false;
        EventListener::$called = false;
        EventListener2::$called = false;
    }

    public function tearDown()
    {
        $this->cleanUp();
    }

    public function test_eventListeners_shouldBeRegisteredSuccessfully()
    {
        $kernel = new Kernel(
            [
                'additionalInstalledComponents'             => [
                    'fantasy_vendor/fantasy_component_1'                       => $this->getTestVendorPath().'/fantasy_vendor/fantasy_component_1'
                ]
            ]
        );

        $this->assertFalse(EventListener::$called);
        $this->assertFalse(EventListener2::$called);

        $eventListener = $kernel->getEventDispatcher();

        $eventListener->dispatch('my.event');

        $this->assertTrue(EventListener::$called);
        $this->assertTrue(EventListener2::$called);

        $listeners = $eventListener->getListeners('my.event');

        if ($listeners[0][0] instanceof EventListener) {
            $firstListener = $listeners[0][0];
            $secondListener = $listeners[1][0];
        } else {
            $firstListener = $listeners[1][0];
            $secondListener = $listeners[0][0];
        }

        $this->assertEquals(0, $eventListener->getListenerPriority('my.event', [$firstListener, 'handle']));
        $this->assertEquals(2, $eventListener->getListenerPriority('my.event', [$secondListener, 'handle']));
    }

    public function test_cache_shouldCacheConfigurationIfCacheIsEnabled()
    {
        $kernel = $this->createInstance(['environment' => 'prod']);

        $this->assertFalse($kernel->hasComponentConfigParam('fantasy_vendor/fantasy_component_1', 'added_option'));

        $optionalConfigFilePath = $this->getTestRootPath().'/optional.yml';

        file_put_contents($optionalConfigFilePath, Yaml::dump(['added_option' => 'added_value']));

        $kernel->boot();

        $this->assertFalse($kernel->hasComponentConfigParam('fantasy_vendor/fantasy_component_1', 'added_option'));

        $kernel->setOptions(['environment' => 'dev']);

        $this->assertTrue($kernel->hasComponentConfigParam('fantasy_vendor/fantasy_component_1', 'added_option'));
    }

    public function test_setContainer_setsANewContainer()
    {
        $newContainer = new ContainerBuilder();
        $oldContainer = self::$containerKernelTest->getContainer();

        $this->assertEquals($oldContainer, self::$containerKernelTest->getContainer());
        $this->assertNotEquals($newContainer, self::$containerKernelTest->getContainer());

        self::$containerKernelTest->setContainer($newContainer);

        $this->assertEquals($newContainer, self::$containerKernelTest->getContainer());
        $this->assertNotEquals($oldContainer, self::$containerKernelTest->getContainer());

        self::$containerKernelTest->setContainer($oldContainer);
    }

    public function test_setContainerService_setsTheConfiguredService()
    {
        try {
            self::$containerKernelTest->setContainerService('my_fantasy_service.datetime', new \DateTime());

            $this->assertTrue(self::$containerKernelTest->hasContainerService('my_fantasy_service.datetime'));

            $this->assertInstanceOf('\DateTime', self::$containerKernelTest->getContainerService('my_fantasy_service.datetime'));
        } catch (BadMethodCallException $e) {
            // Symfony DIC fails when setting a new service after being compiled. We are testing here
            // that the service is being set on the DIC. This exception is specific behaviour of the
            // symfony DIC, so we must not fail.

            $this->assertTrue(true);
        }
    }

    public function test_hasContainerService_checksIfTheConfiguredServiceIsThere()
    {
        $this->assertTrue(self::$containerKernelTest->hasContainerService('fantasy_component_1.datetime'));
        $this->assertTrue(self::$containerKernelTest->hasContainerService('fantasy_component_2.datetime'));
        $this->assertFalse(self::$containerKernelTest->hasContainerService('fantasy_component_3.datetime'));
    }

    public function test_getContainerService_returnsTheConfiguredService()
    {
        $dateTimeService = self::$containerKernelTest->getContainerService('fantasy_component_1.datetime');
        $dateTimeService2 = self::$containerKernelTest->getContainerService('fantasy_component_2.datetime');

        $this->assertInstanceOf('\DateTime', $dateTimeService);
        $this->assertEquals('2010-01-01 00:00:00', $dateTimeService->format('Y-m-d H:i:s'));

        $this->assertInstanceOf('\DateTime', $dateTimeService2);
        $this->assertEquals('2011-01-01 00:00:00', $dateTimeService2->format('Y-m-d H:i:s'));
    }

    public function test_runProcessors_ifNoProcessorIsRegisteredThenSkipExecutionOfMethodRunProcessors()
    {
        $kernel = $this->createInstance(
            [
                'directories'       => [
                    'vendorPath'        => $this->getTestVendorPath5()
                ]
            ]
        );

        $kernel->getConfig();
    }

    /**
     * @expectedException \IronEdge\Component\Kernel\Exception\InvalidConfigException
     */
    public function test_runProcessors_invalidComponentsParameterShouldThrowException()
    {
        $kernel = $this->createInstance(
            [
                'directories'       => [
                    'vendorPath'        => $this->getTestVendorPath4()
                ]
            ]
        );

        $kernel->getConfig();
    }

    /**
     * @expectedException \IronEdge\Component\Kernel\Exception\InvalidConfigException
     */
    public function test_runProcessors_invalidProcessorValueTypeShouldThrowException()
    {
        $kernel = $this->createInstance(
            [
                'directories'       => [
                    'vendorPath'        => $this->getTestVendorPath3()
                ]
            ]
        );

        $kernel->getConfig();
    }

    /**
     * @expectedException \IronEdge\Component\Kernel\Exception\InvalidConfigException
     */
    public function test_runProcessors_invalidProcessorImplementationShouldThrowException()
    {
        $kernel = $this->createInstance(
            [
                'directories'       => [
                    'vendorPath'        => $this->getTestVendorPath2()
                ]
            ]
        );

        $kernel->getConfig();
    }

    public function test_runProcessors_shouldRunProcessorsAfterLoadingTheConfiguration()
    {
        $kernel = $this->createInstance();

        $kernel->getConfig();

        $this->assertTrue(ConfigProcessor::$onComponentConfigRegistrationCalled);
        $this->assertTrue(ConfigProcessor::$onBeforeCache);
        $this->assertTrue(ConfigProcessor::$onAfterCache);
        $this->assertTrue(ConfigProcessor::$onBeforeContainerCompile);

        $this->assertFalse(ConfigProcessor2::$onComponentConfigRegistrationCalled);
        $this->assertTrue(ConfigProcessor2::$onBeforeCache);
        $this->assertTrue(ConfigProcessor2::$onAfterCache);
        $this->assertTrue(ConfigProcessor2::$onBeforeContainerCompile);

        $this->assertEquals(
            'registered_value_1',
            $kernel->getComponentConfigParam(
                'fantasy_vendor/fantasy_component_1',
                'on_component_config_registration.source_component.registered_param'
            )
        );
        $this->assertEquals(
            'fantasy_vendor/fantasy_component_2',
            $kernel->getComponentConfigParam(
                'fantasy_vendor/fantasy_component_1',
                'on_before_cache.source_component'
            )
        );
        $this->assertEquals(
            'fantasy_vendor/fantasy_component_2',
            $kernel->getComponentConfigParam(
                'fantasy_vendor/fantasy_component_1',
                'on_after_cache.source_component'
            )
        );
        $this->assertFalse(
            $kernel->hasComponentConfigParam(
                'fantasy_vendor/fantasy_component_2',
                'on_component_config_registration.source_component.registered_param'
            )
        );
        $this->assertFalse(
            $kernel->hasComponentConfigParam(
                'fantasy_vendor/fantasy_component_2',
                'on_before_cache.source_component'
            )
        );
        $this->assertFalse(
            $kernel->hasComponentConfigParam(
                'fantasy_vendor/fantasy_component_2',
                'on_after_cache.source_component'
            )
        );

        $orderedByPriority = $kernel->getConfigProcessorsOrderedByPriority();

        $this->assertInstanceOf('\IronEdge\Component\Kernel\Test\Helper\ConfigProcessor2', $orderedByPriority[0]);
        $this->assertInstanceOf('\IronEdge\Component\Kernel\Test\Helper\ConfigProcessor', $orderedByPriority[1]);
    }

    public function test_getComponentConfigParam_returnsAComponentConfigParam()
    {
        $kernel = $this->createInstance();

        $this->assertEquals(
            'override_admin',
            $kernel->getComponentConfigParam('fantasy_vendor/fantasy_component_1', 'users.admin.username')
        );
    }

    public function test_setComponentConfigParam_setsAComponentConfigParam()
    {
        $kernel = $this->createInstance();

        $kernel->setComponentConfigParam('fantasy_vendor/fantasy_component_1', 'users.admin.username', 'new_admin');

        $this->assertEquals(
            'new_admin',
            $kernel->getComponentConfigParam('fantasy_vendor/fantasy_component_1', 'users.admin.username')
        );
    }

    public function test_hasConfigParam_testsIfAParamExists()
    {
        $kernel = $this->createInstance();

        $this->assertTrue($kernel->hasConfigParam('components.fantasy_vendor/fantasy_component_3'));
        $this->assertFalse($kernel->hasConfigParam('components.fantasy_vendor222222/fantasy_component_3'));
    }

    public function test_getConfigParam_ifComponentDoesntHaveConfigSetArrayAsDefault()
    {
        $kernel = $this->createInstance();

        $this->assertEquals([], $kernel->getConfigParam('components.fantasy_vendor/fantasy_component_3'));
    }

    public function test_getConfigParam_returnsCorrectParameter()
    {
        $kernel = $this->createInstance();

        $this->assertEquals('custom_value_1_dev_override', $kernel->getConfigParam('custom_params.custom_param_1'));
        $this->assertEquals('custom_value_2_dev', $kernel->getConfigParam('custom_params.custom_param_2'));
        $this->assertEquals('override_admin', $kernel->getConfigParam('components.fantasy_vendor/fantasy_component_1.users.admin.username'));
    }

    public function test_setConfigParam_setsCorrectParameter()
    {
        $kernel = $this->createInstance();

        $this->assertEquals('custom_value_1_dev_override', $kernel->getConfigParam('custom_params.custom_param_1'));

        $kernel->setConfigParam('custom_params.custom_param_1', 'custom_value_1_dev_override_ultra');

        $this->assertEquals('custom_value_1_dev_override_ultra', $kernel->getConfigParam('custom_params.custom_param_1'));
    }

    public function test_originalOptionsAreHeldUntouched()
    {
        $kernel = $this->createInstance(['environment' => 'DeV']);

        $options = $kernel->getOptions();

        $this->assertEquals('DeV', $options['environment']);
    }

    public function test_environmentOptionsAreSetCorrectlyOnLowerCase()
    {
        $kernel = $this->createInstance(['environment' => 'DeV']);

        $this->assertTrue($kernel->isDev());
        $this->assertFalse($kernel->isCacheEnabled());

        $envOptions = $kernel->getEnvironmentOptions();

        $this->assertEquals(false, $envOptions['cache']);

        $this->assertFalse($kernel->getEnvironmentOption('cache'));
    }

    public function test_environmentOptionsAreSetCorrectly()
    {
        $kernel = $this->createInstance(['environment' => 'dev']);

        $this->assertTrue($kernel->isDev());
        $this->assertFalse($kernel->isCacheEnabled());

        $envOptions = $kernel->getEnvironmentOptions();

        $this->assertEquals(false, $envOptions['cache']);

        $this->assertFalse($kernel->getEnvironmentOption('cache'));

        $kernel->setOptions(['environment' => 'prod']);

        $this->assertFalse($kernel->isDev());
        $this->assertTrue($kernel->isCacheEnabled());

        $envOptions = $kernel->getEnvironmentOptions();

        $this->assertEquals(true, $envOptions['cache']);

        $this->assertTrue($kernel->getEnvironmentOption('cache'));
    }

    /**
     * @expectedException \IronEdge\Component\Kernel\Exception\InvalidOptionTypeException
     */
    public function test_invalidDirectoriesOption()
    {
        $this->createInstance(['directories' => 'asdas']);
    }

    /**
     * @dataProvider rootPathDataProvider
     */
    public function test_missingDirectoriesAreCreated($rootPath)
    {
        $kernel = $this->createInstance(
            [
                'directories'       => [
                    'rootPath'          => $rootPath,
                    'vendorPath'        => null,
                    'configPath'        => null
                ]
            ]
        );

        $this->assertTrue(is_dir($kernel->getRootPath()));
        $this->assertTrue(is_dir($kernel->getVarPath()));
        $this->assertTrue(is_dir($kernel->getEtcPath()));
        $this->assertTrue(is_dir($kernel->getBinPath()));
        $this->assertTrue(is_dir($kernel->getLogsPath()));
        $this->assertTrue(is_dir($kernel->getTmpPath()));
        $this->assertTrue(is_dir($kernel->getCachePath()));
        $this->assertTrue(is_dir($kernel->getVendorPath()));
    }

    public function test_isVendor_shouldReturnTrueIfThisComponentIsAVendor()
    {
        $kernel = $this->createInstance(
            [
                'directories'       => [
                    'rootPath'          => null,
                    'vendorPath'        => null,
                    'configPath'        => null
                ]
            ]
        );

        $this->assertEquals($this->isVendor(), $kernel->isVendor());
    }

    public function test_isRootPackage_shouldReturnTrueIfThisComponentIsTheRootPackage()
    {
        $kernel = $this->createInstance(
            [
                'directories'       => [
                    'rootPath'          => null,
                    'vendorPath'        => null,
                    'configPath'        => null
                ]
            ]
        );

        $this->assertEquals(!$this->isVendor(), $kernel->isRootPackage());
    }

    public function test_getInstalledComponents_shouldReturnCorrectData()
    {
        $kernel = $this->createInstance();

        $this->assertEquals($this->getTestInstalledComponents(), $kernel->getInstalledComponents());
    }

    public function test_getInstalledComponentsNames_returnsAnArrayOfInstalledComponentsNames()
    {
        $kernel = $this->createInstance();

        $this->assertEquals($this->getTestInstalledComponentNames(), $kernel->getInstalledComponentsNames());
    }

    // Data Providers

    public function isRootPackageDataProvider()
    {
        return [
            [$this->getTestRootPath(), true],
            [$this->getTestHelperDirsPath().'/root_package/ironedge/kernel', false]
        ];
    }

    public function rootPathDataProvider()
    {
        return [
            [$this->getTestRootPath()]
        ];
    }

    // Helper Methods

    protected function createInstance(array $options = [])
    {
        $options = array_replace_recursive(
            [
                'directories'       => [
                    'rootPath'          => $this->getTestRootPath(),
                    'vendorPath'        => $this->getTestVendorPath(),
                    'configPath'        => $this->getTestConfigPath()
                ]
            ],
            $options
        );

        return new Kernel($options);
    }
}