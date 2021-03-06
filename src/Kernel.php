<?php

/*
 * This file is part of the kernel package.
 *
 * (c) Gustavo Falco <comfortablynumb84@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IronEdge\Component\Kernel;

use Doctrine\Common\Cache\CacheProvider;
use IronEdge\Component\Cache\Factory;
use IronEdge\Component\Config\Config;
use IronEdge\Component\Config\ConfigInterface;
use IronEdge\Component\Kernel\Config\ProcessorInterface;
use IronEdge\Component\Kernel\EventDispatcher\EventDispatcher;
use IronEdge\Component\Kernel\Exception\InvalidConfigException;
use IronEdge\Component\Kernel\Exception\CantCreateDirectoryException;
use IronEdge\Component\Kernel\Exception\DirectoryIsNotWritable;
use IronEdge\Component\Kernel\Exception\InvalidOptionTypeException;
use IronEdge\Component\Kernel\Exception\MissingDirectoryException;
use IronEdge\Component\Kernel\Exception\VendorsNotInstalledException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/*
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 */
class Kernel implements KernelInterface
{
    const KERNEL_CACHE_INSTANCE_ID = 'ironedge.kernel';
    const KERNEL_CACHE_CONFIG_ID = 'configuration';


    /**
     * A list of installed components.
     *
     * @var array
     */
    private $_installedComponents;

    /**
     * Is this component a vendor?
     *
     * @var bool
     */
    private $_isVendor;

    /**
     * Array of directories used by this Kernel instance.
     *
     * @var array
     */
    private $_directories = [];

    /**
     * Configuration object.
     *
     * @var ConfigInterface
     */
    private $_config;

    /**
     * Array of config processors, using the component name which registered the processor as the key.
     *
     * @var array
     */
    private $_configProcessorsByComponent;

    /**
     * Array of config processors, ordered by priority.
     *
     * @var array
     */
    private $_configProcessorsByPriority;

    /**
     * Kernel Options.
     *
     * @var array
     */
    private $_options = [];

    /**
     * Environment.
     *
     * @var string
     */
    private $_environment;

    /**
     * Environment options.
     *
     * @var array
     */
    private $_environmentOptions = [];

    /**
     * Field _container.
     *
     * @var Container
     */
    private $_container;

    /**
     * Field _cacheFactory.
     *
     * @var Factory
     */
    private $_cacheFactory;

    /**
     * Was the configuration initialized?
     *
     * @var bool
     */
    private $_configurationWasInitialized = false;


    /**
     * Constructor.
     *
     * @param array $options - Options.
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    /**
     * Method getEnvironment.
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->_environment;
    }

    /**
     * Returns the current environment options.
     *
     * @return array
     */
    public function getEnvironmentOptions()
    {
        return $this->_environmentOptions;
    }

    /**
     * Returns a specific environment option, or $default if option $name does not exist.
     *
     * @param string $name - Option name.
     * @param mixed $default - Default value if option does not exist.
     *
     * @return mixed
     */
    public function getEnvironmentOption($name, $default = null)
    {
        return array_key_exists($name, $this->_environmentOptions) ?
            $this->_environmentOptions[$name] :
            $default;
    }

    /**
     * Is the current environment "dev"?
     *
     * @return bool
     */
    public function isDev()
    {
        return $this->getEnvironment() === 'dev';
    }

    /**
     * Is cache enabled?
     *
     * @return bool
     */
    public function isCacheEnabled()
    {
        return $this->getEnvironmentOption('cache');
    }

    /**
     * Returns the path to the root directory. If this is a vendor, it will be the path to the root project.
     * If this is the root project, then it will be the path to the root of this component.
     *
     * @return string
     */
    public function getRootPath()
    {
        if ($this->getDirectory('rootPath') === null) {
            $this->_directories['rootPath'] = dirname($this->getVendorPath());
        }

        return $this->getDirectory('rootPath');
    }

    /**
     * Returns the path to the bin directory.
     *
     * @return string
     */
    public function getBinPath()
    {
        if ($this->getDirectory('binPath') === null) {
            $this->_directories['binPath'] = $this->getRootPath().'/bin';
        }

        return $this->getDirectory('binPath');
    }

    /**
     * Returns the path to the var directory.
     *
     * @return string
     */
    public function getVarPath()
    {
        if ($this->getDirectory('varPath') === null) {
            $this->_directories['varPath'] = $this->getRootPath().'/var';
        }

        return $this->getDirectory('varPath');
    }

    /**
     * Returns the path to the etc directory.
     *
     * @return string
     */
    public function getEtcPath()
    {
        if ($this->getDirectory('etcPath') === null) {
            $this->_directories['etcPath'] = $this->getRootPath().'/etc';
        }

        return $this->getDirectory('etcPath');
    }

    /**
     * Returns the path to the config files directory.
     *
     * @return string
     */
    public function getConfigPath()
    {
        if ($this->getDirectory('configPath') === null) {
            $this->_directories['configPath'] = $this->getEtcPath().'/config';
        }

        return $this->getDirectory('configPath');
    }

    /**
     * Returns the path to the common.yml configuration file.
     *
     * @return string
     */
    public function getConfigCommonFilePath()
    {
        return $this->getConfigPath().'/config.yml';
    }

    /**
     * Returns the path to the config_%environment%.yml configuration file.
     *
     * @return string
     */
    public function getConfigEnvironmentFilePath()
    {
        return $this->getConfigPath().'/config_'.$this->getEnvironment().'.yml';
    }

    /**
     * Returns the path to the custom.yml configuration file.
     *
     * @return string
     */
    public function getConfigCustomFilePath()
    {
        return $this->getConfigPath().'/custom.yml';
    }

    /**
     * Returns the path to the directory which holds the log files.
     *
     * @return string
     */
    public function getLogsPath()
    {
        if ($this->getDirectory('logsPath') === null) {
            $this->_directories['logsPath'] = $this->getVarPath().'/logs';
        }

        return $this->getDirectory('logsPath');
    }

    /**
     * Returns the path to the directory which holds the temporary files.
     *
     * @return string
     */
    public function getTmpPath()
    {
        if ($this->getDirectory('tmpPath') === null) {
            $this->_directories['tmpPath'] = $this->getVarPath().'/tmp';
        }

        return $this->getDirectory('tmpPath');
    }

    /**
     * Returns the path to the directory which holds the cache files.
     *
     * @return string
     */
    public function getCachePath()
    {
        if ($this->getDirectory('cachePath') === null) {
            $this->_directories['cachePath'] = $this->getVarPath().'/cache';
        }

        return $this->getDirectory('cachePath');
    }

    /**
     * Returns the path to the "vendor" directory.
     *
     * @throws VendorsNotInstalledException
     *
     * @return string
     */
    public function getVendorPath()
    {
        if ($this->getDirectory('vendorPath') === null) {
            $dir = $this->isVendor() ?
                __DIR__.'/../../../' :
                __DIR__.'/../vendor';

            if (!($dir = realpath($dir))) {
                throw VendorsNotInstalledException::create();
            }

            $this->_directories['vendorPath'] = $dir;
        }

        return $this->getDirectory('vendorPath');
    }

    /**
     * Returns a list of the directories this Kernel use.
     *
     * @return array
     */
    public function getDirectories()
    {
        return $this->_directories;
    }

    /**
     * Returns the path to a directory.
     *
     * @param string $name - Directory name.
     * @param string $default - Default directory path.
     *
     * @return string
     */
    public function getDirectory($name, $default = null)
    {
        return array_key_exists($name, $this->_directories) ?
            $this->_directories[$name] :
            $default;
    }

    /**
     * Determines if this component is a vendor or the root project.
     *
     * @return bool
     */
    public function isVendor()
    {
        if ($this->_isVendor === null) {
            $this->_isVendor = is_dir(__DIR__.'/../../../ironedge/kernel')
                && is_file(__DIR__.'/../../../composer/installed.json');
        }

        return $this->_isVendor;
    }

    /**
     * Returns true if this component is the root package.
     *
     * @return bool
     */
    public function isRootPackage()
    {
        return !$this->isVendor();
    }

    /**
     * Returns an array of installed component names. Example: ["ironedge/kernel", "myvendor/mycomponent"],
     *
     * @return array
     */
    public function getInstalledComponentsNames()
    {
        return array_keys($this->getInstalledComponents());
    }

    /**
     * Returns an array of installed component, each element being component-name => component-path.
     *
     * Example:
     *
     * {"ironedge/kernel" => "/path/to/ironedge/kernel", "myvendor/mycomponent" => "/path/to/myvendor/mycomponent}
     *
     * @throws InvalidConfigException
     *
     * @return array
     */
    public function getInstalledComponents()
    {
        if ($this->_installedComponents === null) {
            $vendorPath = $this->getVendorPath();
            $glob = glob($vendorPath.'/*/*');

            $this->_installedComponents = [];

            foreach ($glob as $globElement) {
                if (!is_file($globElement.'/composer.json')) {
                    continue;
                }

                $this->_installedComponents[basename(dirname($globElement)).'/'.basename($globElement)] = $globElement;
            }

            // Add additional installed components

            $additionalInstalledComponents = $this->getOption('additionalInstalledComponents', []);

            if ($additionalInstalledComponents) {
                foreach ($additionalInstalledComponents as $componentName => $componentPath) {
                    if (!is_dir($componentPath)) {
                        throw InvalidConfigException::create(
                            'Option "additionalInstalledComponents" as a component "'.$componentName.'" with a path '.
                            '"'.$componentPath.'" which does not exist.'
                        );
                    }

                    $additionalInstalledComponents[$componentName] = realpath($componentPath);
                }

                $this->_installedComponents = array_replace(
                    $this->_installedComponents,
                    $additionalInstalledComponents
                );
            }

            ksort($this->_installedComponents);

            // Add the root component at the end of the array

            $this->_installedComponents['ROOT/PACKAGE'] = $this->getRootPath();
        }

        return $this->_installedComponents;
    }

    /**
     * Method getConfig.
     *
     * @return ConfigInterface
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Returns a configuration parameter.
     *
     * @param string $name - Param name.
     * @param mixed $default - Default value in case the param does not exist.
     * @param array $options - Options.
     *
     * @return mixed
     */
    public function getConfigParam($name, $default = null, array $options = [])
    {
        return $this->getConfig()->get($name, $default, $options);
    }

    /**
     * Sets a configuration parameter.
     *
     * @param string $name - Param name.
     * @param mixed $value - Value.
     * @param array $options - Options.
     *
     * @return mixed
     */
    public function setConfigParam($name, $value, array $options = [])
    {
        return $this->getConfig()->set($name, $value, $options);
    }

    /**
     * Checks if a configuration parameter exists.
     *
     * @param string $name - Param name.
     * @param array $options - Options.
     *
     * @return bool
     */
    public function hasConfigParam($name, array $options = [])
    {
        return $this->getConfig()->has($name, $options);
    }

    /**
     * Returns a configuration parameter of a component.
     *
     * @param string $componentName - Component name.
     * @param string $paramName - Param name.
     * @param mixed $default - Default value in case the param does not exist.
     * @param array $options - Options.
     *
     * @return mixed
     */
    public function getComponentConfigParam($componentName, $paramName, $default = null, array $options = [])
    {
        return $this->getConfig()->get('components.'.$componentName.'.'.$paramName, $default, $options);
    }

    /**
     * Sets a configuration parameter of a component.
     *
     * @param string $componentName - Component name.
     * @param string $paramName - Param name.
     * @param mixed $value - Value.
     * @param array $options - Options.
     *
     * @return mixed
     */
    public function setComponentConfigParam($componentName, $paramName, $value, array $options = [])
    {
        return $this->getConfig()->set('components.'.$componentName.'.'.$paramName, $value, $options);
    }

    /**
     * Checks if a configuration parameter of a component exists.
     *
     * @param string $componentName - Component name.
     * @param string $paramName - Param name.
     * @param array $options - Options.
     *
     * @return mixed
     */
    public function hasComponentConfigParam($componentName, $paramName, array $options = [])
    {
        return $this->getConfig()->has('components.'.$componentName.'.'.$paramName, $options);
    }

    /**
     * Loads the config instance.
     *
     * @param bool $refresh - Load again even if it was already loaded?
     *
     * @return void
     */
    public function initializeConfig($refresh = false)
    {
        if (!$this->_configurationWasInitialized || $refresh) {
            $cache = $this->getKernelCache($refresh);

            $this->_config = new Config(
                [],
                [
                    'templateVariables' => $this->getOption('configTemplateVariables', [])
                ]
            );

            if (!($config = $cache->fetch(self::KERNEL_CACHE_CONFIG_ID))) {
                $this->loadComponentsConfigFiles();

                $this->loadRootProjectConfigFiles();

                $this->runOnComponentConfigRegistrationProcessorMethod();

                $this->runOnBeforeCacheMethod();

                $cache->save(self::KERNEL_CACHE_CONFIG_ID, $this->_config->getData());
            } else {
                $this->_config->setData($config);
            }

            $this->runOnAfterCacheMethod();

            $this->_configurationWasInitialized = true;
        }
    }

    /**
     * Sets the options for this Kernel instance.
     *
     * @param array $options - options.
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->_options = array_replace_recursive(
            [
                'createDirectories'                 => true,
                'environment'                       => 'dev',
                'environmentsOptions'               => [
                    'defaults' => [
                        'cache' => false
                    ],
                    'prod'     => [
                        'cache' => true
                    ],
                    'staging'  => [
                        'cache' => true
                    ]
                ],
                'additionalInstalledComponents'     => [],
                'directories'                       => [
                    'rootPath'   => null,
                    'vendorPath' => null,
                    'logsPath'   => null,
                    'etcPath'    => null,
                    'configPath' => null,
                    'binPath'    => null,
                    'tmpPath'    => null,
                    'cachePath'  => null,
                    'varPath'    => null
                ],
                'configTemplateVariables'           => []
            ],
            $options
        );

        $this->boot();

        return $this;
    }

    /**
     * Returns the Kernel options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Returns a specific option, or $default if option $name does not exist.
     *
     * @param string $name - Option name.
     * @param mixed $default - Default value if option does not exist.
     *
     * @return mixed
     */
    public function getOption($name, $default = null)
    {
        return array_key_exists($name, $this->_options) ?
            $this->_options[$name] :
            $default;
    }

    /**
     * Returns the DIC instance.
     *
     * @return Container
     */
    public function getContainer()
    {
        return $this->_container;
    }

    /**
     * Sets the DIC.
     *
     * @param Container $container - Container.
     *
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->_container = $container;

        return $this;
    }

    /**
     * Returns a service with ID $id from the DIC.
     *
     * @param string $serviceId - Service ID.
     * @param integer $invalidBehavior - Invalid Behaviour.
     *
     * @return object
     */
    public function getContainerService(
        $serviceId,
        $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE
    ) {
        return $this->getContainer()->get($serviceId, $invalidBehavior);
    }

    /**
     * Verifies if the DIC has a service with id $id.
     *
     * @param string $serviceId - Service ID.
     *
     * @return bool
     */
    public function hasContainerService($serviceId)
    {
        return $this->getContainer()->has($serviceId);
    }

    /**
     * Sets a service on the DIC.
     *
     * @param string $serviceId - Service ID.
     * @param object $service - Service instance.
     *
     * @return $this
     */
    public function setContainerService($serviceId, $service)
    {
        $this->getContainer()->set($serviceId, $service);

        return $this;
    }

    /**
     * Returns the event dispatcher.
     *
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->getContainerService('event_dispatcher');
    }

    /**
     * Returns the instance of the cache factory.
     *
     * @return Factory
     */
    public function getCacheFactory()
    {
        if ($this->_cacheFactory === null) {
            $this->_cacheFactory = new Factory();
        }

        return $this->_cacheFactory;
    }

    /**
     * Returns the kernel cache provider instance.
     *
     * @param bool $refresh - Re-create the instance even if it's already created.
     *
     * @throws \IronEdge\Component\Cache\Exception\InvalidConfigException
     * @throws \IronEdge\Component\Cache\Exception\InvalidTypeException
     * @throws \IronEdge\Component\Cache\Exception\MissingExtensionException
     *
     * @return \Doctrine\Common\Cache\CacheProvider
     */
    public function getKernelCache($refresh = false)
    {
        return $this->getCacheFactory()->create(
            self::KERNEL_CACHE_INSTANCE_ID,
            $this->isCacheEnabled() ?
                'filesystem' :
                'void',
            [
                'directory' => $this->getCachePath().'/ironedge/kernel'
            ],
            $refresh
        );
    }

    /**
     * Clears the cache of every cache instance, and the DIC cache.
     *
     * @return void
     */
    public function clearCache()
    {
        /** @var CacheProvider $cache */
        foreach ($this->getCacheFactory()->getInstances() as $cache) {
            $cache->deleteAll();
        }

        $this->log('info', 'Cleared all Cache Instances.');

        // The DIC has its own cache mechanism. We need to clear it too

        @unlink($this->getContainerCacheFilePath());

        $this->log('info', 'Cleared Container Cache.');
    }

    /**
     * Returns the logger factory.
     *
     * @return \IronEdge\Component\Logger\Factory
     */
    public function getLoggerFactory()
    {
        return $this->getContainerService('logger.factory');
    }

    /**
     * Returns a specific logger instance. If no name is provided, we return the "default" logger.
     *
     * @param string $name - Name.
     *
     * @return LoggerInterface
     */
    public function getLogger($name = 'default')
    {
        return $this->getContainerService('logger.'.$name);
    }

    /**
     * Returns true if a logger is registered.
     *
     * @param string $name - Name.
     *
     * @return bool
     */
    public function hasLogger($name = 'default')
    {
        return $this->hasContainerService('logger.'.$name);
    }

    /**
     * Boots the Kernel. Please note that the configuration and the DIC container are NOT loaded
     * on this method. They are lazy loaded.
     *
     * @return void
     */
    public function boot()
    {
        $this->initializeDirectories();
        $this->initializeEnvironment();
        $this->initializeConfigTemplateVariables();
        $this->initializeConfig(true);
        $this->initializeContainer(true);

        $this->log('info', 'Kernel has booted.', ['env' => $this->getEnvironment()]);
    }

    /**
     * Returns an array of registered config processors.
     *
     * @return array
     */
    public function getConfigProcessors()
    {
        $this->initializeConfigProcessors();

        return $this->_configProcessorsByComponent;
    }

    /**
     * Returns an array of registered config processors, ordered by priority.
     *
     * @return array
     */
    public function getConfigProcessorsOrderedByPriority()
    {
        $this->initializeConfigProcessors();

        return $this->_configProcessorsByPriority;
    }

    /**
     * Runs the method "onBeforeCache" method of the configuration processors.
     *
     * @throws InvalidConfigException
     *
     * @return void
     */
    protected function runOnBeforeCacheMethod()
    {
        /** @var ProcessorInterface $processor */
        foreach ($this->getConfigProcessorsOrderedByPriority() as $processor) {
            $processor->onBeforeCache($this, $this->getConfig());
        }
    }

    /**
     * Runs the method "onAfterCache" method of the configuration processors.
     *
     * @throws InvalidConfigException
     *
     * @return void
     */
    protected function runOnAfterCacheMethod()
    {
        /** @var ProcessorInterface $processor */
        foreach ($this->getConfigProcessorsOrderedByPriority() as $processor) {
            $processor->onAfterCache($this, $this->getConfig());
        }
    }

    /**
     * Runs the method "onBeforeContainerCompile" method of the configuration processors.
     *
     * @param ContainerBuilder $containerBuilder - Container Builder.
     *
     * @throws InvalidConfigException
     *
     * @return void
     */
    protected function runOnBeforeContainerCompileMethod(ContainerBuilder $containerBuilder)
    {
        /** @var ProcessorInterface $processor */
        foreach ($this->getConfigProcessorsOrderedByPriority() as $processor) {
            $processor->onBeforeContainerCompile($this, $this->getConfig(), $containerBuilder);
        }
    }

    /**
     * Runs the configuration processors.
     *
     * @throws InvalidConfigException
     *
     * @return void
     */
    protected function runOnComponentConfigRegistrationProcessorMethod()
    {
        $configProcessors = $this->getConfigProcessors();

        if (!$configProcessors) {
            return;
        }

        $componentNames = $this->getInstalledComponentsNames();

        // First, we run the "onComponentConfigRegistration" method

        foreach ($componentNames as $componentName) {
            if (!$this->hasComponentConfigParam($componentName, 'components')) {
                continue;
            }

            $componentsConfig = $this->getComponentConfigParam($componentName, 'components');

            if (!is_array($componentsConfig)) {
                throw InvalidConfigException::create('"components" configuration parameter must be an array.');
            }

            foreach ($componentsConfig as $targetComponentName => $registeredConfig) {
                if (!isset($configProcessors[$targetComponentName])) {
                    continue;
                }

                /** @var ProcessorInterface $processor */
                $processor = $configProcessors[$targetComponentName];

                $processor->onComponentConfigRegistration(
                    $this,
                    $this->getConfig(),
                    $componentName,
                    $targetComponentName,
                    $registeredConfig
                );
            }
        }
    }

    /**
     * Logs a message.
     *
     * @param string $level   - Level.
     * @param string $msg     - Message.
     * @param array  $context - Context.
     *
     * @return $this
     */
    protected function log($level = 'info', $msg, array $context = [])
    {
        if ($this->hasLogger()) {
            $this->getLogger()->$level('[Kernel] '.$msg, $context);
        }

        return $this;
    }

    /**
     * Loads configuration files from the root project.
     *
     * @return void
     */
    protected function loadRootProjectConfigFiles()
    {
        $files = [
            $this->getConfigCommonFilePath(),
            $this->getConfigEnvironmentFilePath(),
            $this->getConfigCustomFilePath()
        ];

        foreach ($files as $file) {
            if (is_file($file)) {
                $this->_config->load(['file' => $file, 'processImports' => true]);
            }
        }
    }

    /**
     * Loads configuration files from the installed components.
     *
     * @return void
     */
    protected function loadComponentsConfigFiles()
    {
        // First we load the component's config files

        $installedComponents = $this->getInstalledComponents();

        foreach ($installedComponents as $componentName => $componentPath) {
            $file = $componentPath.'/frenzy/config.yml';

            if (!is_file($file)) {
                $this->_config->set('components.'.$componentName, []);

                continue;
            }

            $this->_config->load(['file' => $file, 'loadInKey' => 'components.'.$componentName, 'processImports' => true]);
        }
    }

    /**
     * Initializes config processors.
     *
     * @throws InvalidConfigException
     *
     * @return void
     */
    protected function initializeConfigProcessors()
    {
        if ($this->_configProcessorsByComponent === null) {
            $this->_configProcessorsByComponent = [];
            $this->_configProcessorsByPriority = [];
            $processorsOrderedByPriority = [];
            $componentsNames = $this->getInstalledComponentsNames();

            foreach ($componentsNames as $componentName) {
                if (!$this->hasComponentConfigParam($componentName, 'components.ironedge/kernel.config.processor.class')) {
                    continue;
                }

                $processorClass = $this->getComponentConfigParam(
                    $componentName,
                    'components.ironedge/kernel.config.processor.class'
                );

                if (!is_string($processorClass)) {
                    throw InvalidConfigException::create(
                        'Error in configuration of component "'.$componentName.'": '.
                        'Configuration "components.ironedge/kernel.config.processor.class" must be a string. Received: '.
                        print_r($processorClass, true)
                    );
                }

                $processor = new $processorClass();

                if (!($processor instanceof ProcessorInterface)) {
                    throw InvalidConfigException::create(
                        'Error in configuration of component "'.$componentName.'": '.
                        'Configuration "components.ironedge/kernel.config.processor.class" must be a class of instance '.
                        '"IronEdge\Component\Kernel\Config\ProcessorInterface".'
                    );
                }

                $processorPriority = (int) $this->getComponentConfigParam(
                    $componentName,
                    'components.ironedge/kernel.config.processor.priority',
                    0
                );

                if (!isset($processorsOrderedByPriority[$processorPriority])) {
                    $processorsOrderedByPriority[$processorPriority] = [];
                }

                $processorsOrderedByPriority[$processorPriority][] = $processor;
                $this->_configProcessorsByComponent[$componentName] = $processor;
            }

            // Order by priority. The greater the number, the higher is the priority.

            krsort($processorsOrderedByPriority, SORT_NUMERIC);

            foreach ($processorsOrderedByPriority as $priority => $processors) {
                $this->_configProcessorsByPriority = array_merge(
                    $this->_configProcessorsByPriority,
                    $processors
                );
            }
        }
    }

    /**
     * Initializes the template variables used on the config instance.
     *
     * @return void
     */
    protected function initializeConfigTemplateVariables()
    {
        $this->_options['configTemplateVariables'] = array_replace(
            $this->_options['configTemplateVariables'],
            [
                '%kernel.root_path%'   => $this->getRootPath(),
                '%kernel.vendor_path%' => $this->getVendorPath(),
                '%kernel.logs_path%'   => $this->getLogsPath(),
                '%kernel.etc_path%'    => $this->getEtcPath(),
                '%kernel.config_path%' => $this->getConfigPath(),
                '%kernel.bin_path%'    => $this->getBinPath(),
                '%kernel.tmp_path%'    => $this->getTmpPath(),
                '%kernel.cache_path%'  => $this->getCachePath(),
                '%kernel.var_path%'    => $this->getVarPath()
            ]
        );
    }

    /**
     * Initializes the DIC.
     *
     * @param bool $refresh - Reinitialize the DIC?
     *
     * @return void
     */
    protected function initializeContainer($refresh = false)
    {
        if ($this->_container === null || $refresh) {
            $file = $this->getContainerCacheFilePath();
            $containerClassName = 'IronEdgeKernelContainer_'.sha1($file);

            if (!$this->isCacheEnabled() || !is_file($file)) {
                $containerBuilder = new ContainerBuilder();
                $installedComponents = $this->getInstalledComponents();

                foreach ($installedComponents as $componentName => $componentPath) {
                    $path = $componentPath.'/frenzy/services.xml';

                    if (is_file($path)) {
                        $loader = new XmlFileLoader($containerBuilder, new FileLocator(dirname($path)));
                        $loader->load('services.xml');
                    }
                }

                foreach ($this->getOption('configTemplateVariables', []) as $key => $value) {
                    $containerBuilder->setParameter(substr($key, 1, -1), $value);
                }

                $this->runOnBeforeContainerCompileMethod($containerBuilder);

                $containerBuilder->compile();

                $dumper = new PhpDumper($containerBuilder);
                $dir = dirname($file);

                if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                    throw new \RuntimeException('Couldn\'t create directory "'.$dir.'" to dump the DIC.');
                }

                if (!file_put_contents($file, $dumper->dump(['class' => $containerClassName]))) {
                    throw new \RuntimeException('Couldn\'t dump container in file "'.$file.'".');
                }
            }

            require_once $file;

            $containerClassName = '\\'.$containerClassName;

            $this->_container = new $containerClassName();

            $this->_container->set('kernel', $this);
            $this->_container->set('config', $this->getConfig());
        }
    }

    /**
     * Returns the path to the cached DIC instance.
     *
     * @return string
     */
    protected function getContainerCacheFilePath()
    {
        return $this->getCachePath().'/ironedge/kernel/container.php';
    }

    /**
     * Initializes the Environment
     *
     * @return void
     */
    protected function initializeEnvironment()
    {
        $this->_environment = strtolower($this->getOption('environment'));

        $allEnvironmentsOptions = $this->getOption('environmentsOptions');
        $this->_environmentOptions = isset($allEnvironmentsOptions[$this->_environment]) ?
            array_replace_recursive(
                $allEnvironmentsOptions['defaults'],
                $allEnvironmentsOptions[$this->_environment]
            ) :
            $allEnvironmentsOptions['defaults'];
    }

    /**
     * Verifies that all directories exist and that they have proper permissions.
     *
     * @throws CantCreateDirectoryException
     * @throws DirectoryIsNotWritable
     * @throws InvalidOptionTypeException
     * @throws MissingDirectoryException
     *
     * @return void
     */
    protected function initializeDirectories()
    {
        $directories = $this->getOption('directories', []);

        if (!is_array($directories)) {
            throw InvalidOptionTypeException::create('directories', 'array');
        }

        $this->_directories = array_replace(
            $this->_directories,
            $directories
        );

        foreach ($this->getDirectories() as $name => $dir) {
            if ($dir === null) {
                $method = 'get'.ucfirst($name);

                if (method_exists($this, $method)) {
                    $dir = $this->$method();
                    $this->_directories[$name] = $dir;
                }
            }

            if (!is_dir($dir)) {
                if (!$this->getOption('createDirectories')) {
                    throw MissingDirectoryException::create($name, $dir);
                }

                if (!@mkdir($dir, 0777, true)) {
                    throw CantCreateDirectoryException::create($name, $dir);
                }
            }

            switch ($name) {
                case 'logsPath':
                case 'cachePath':
                case 'tmpPath':
                    if (!is_writable($dir)) {
                        throw DirectoryIsNotWritable::create($name, $dir);
                    }

                    break;
                default:
                    break;
            }
        }
    }
}