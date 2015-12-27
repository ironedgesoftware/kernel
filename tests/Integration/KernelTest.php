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

/*
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 */
class KernelTest extends AbstractTestCase
{
    public function setUp()
    {
        $this->cleanUp();

        ConfigProcessor::$onComponentConfigRegistrationCalled = false;
        ConfigProcessor::$onAfterProcessCalled = false;
        ConfigProcessor2::$onComponentConfigRegistrationCalled = false;
        ConfigProcessor2::$onAfterProcessCalled = false;
    }

    public function tearDown()
    {
        $this->cleanUp();
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
        $this->assertTrue(ConfigProcessor::$onAfterProcessCalled);

        $this->assertFalse(ConfigProcessor2::$onComponentConfigRegistrationCalled);
        $this->assertTrue(ConfigProcessor2::$onAfterProcessCalled);

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
                'on_after_process.source_component'
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
                'on_after_process.source_component'
            )
        );
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
        $kernel = $this->createInstance(
            [
                'directories'       => [
                    'rootPath'          => null,
                    'vendorPath'        => null,
                    'configPath'        => null
                ]
            ]
        );
        $installedComponents = $kernel->getInstalledComponents();
        $expectedInstalledComponents = $this->getInstalledComponents();

        $this->assertEquals($expectedInstalledComponents, $installedComponents);
    }

    public function test_getInstalledComponentsNames_returnsAnArrayOfInstalledComponentsNames()
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

        $this->assertEquals($this->getInstalledComponentsNames(), $kernel->getInstalledComponentsNames());
    }

    // Data Providers

    public function rootPathDataProvider()
    {
        return [
            [null],
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