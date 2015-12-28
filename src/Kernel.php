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

use IronEdge\Component\Config\Config;
use IronEdge\Component\Config\ConfigInterface;
use IronEdge\Component\Kernel\Config\ProcessorInterface;
use IronEdge\Component\Kernel\Exception\InvalidConfigException;
use IronEdge\Component\Kernel\Exception\CantCreateDirectoryException;
use IronEdge\Component\Kernel\Exception\DirectoryIsNotWritable;
use IronEdge\Component\Kernel\Exception\InvalidOptionTypeException;
use IronEdge\Component\Kernel\Exception\VendorsNotInstalledException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/*
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 */
class Kernel implements KernelInterface
{
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
     * @var ContainerInterface
     */
    private $_container;

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
     * @param string $name    - Option name.
     * @param mixed  $default - Default value if option does not exist.
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
     * @param string $name    - Directory name.
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
     * Determines if this component is a vendor, or it's a root project.
     *
     * @return bool
     */
    public function isVendor()
    {
        if ($this->_isVendor === null) {
            $this->_isVendor = !is_dir(__DIR__.'/../vendor');
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

            ksort($this->_installedComponents);
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
        if ($this->_config === null) {
            $this->initializeConfig();
        }

        return $this->_config;
    }

    /**
     * Returns a configuration parameter.
     *
     * @param string $name    - Param name.
     * @param mixed  $default - Default value in case the param does not exist.
     * @param array  $options - Options.
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
     * @param string $name    - Param name.
     * @param mixed  $value   - Value.
     * @param array  $options - Options.
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
     * @param string $name    - Param name.
     * @param array  $options - Options.
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
     * @param string $paramName     - Param name.
     * @param mixed  $default       - Default value in case the param does not exist.
     * @param array  $options       - Options.
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
     * @param string $paramName     - Param name.
     * @param mixed  $value         - Value.
     * @param array  $options       - Options.
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
     * @param string $paramName     - Param name.
     * @param array  $options       - Options.
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
            $this->_config = new Config();

            $this->loadComponentsConfigFiles();

            $this->loadRootProjectConfigFiles();

            $this->runConfigProcessors();

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
                'environment'           => 'dev',
                'environmentsOptions'   => [
                    'defaults'              => [
                        'cache'                 => false
                    ],
                    'prod'                  => [
                        'cache'                 => true
                    ],
                    'staging'               => [
                        'cache'                 => true
                    ]
                ],
                'directories'           => [
                    'rootPath'              => null,
                    'vendorPath'            => null,
                    'logsPath'              => null,
                    'etcPath'               => null,
                    'configPath'            => null,
                    'binPath'               => null,
                    'tmpPath'               => null,
                    'cachePath'             => null,
                    'varPath'               => null
                ]
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
     * @param string $name    - Option name.
     * @param mixed  $default - Default value if option does not exist.
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
     * @return ContainerInterface
     */
    public function getContainer()
    {
        $this->initializeContainer();

        return $this->_container;
    }

    /**
     * Sets the DIC.
     *
     * @param ContainerInterface $container - Container.
     *
     * @return $this
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->_container = $container;

        return $this;
    }

    /**
     * Returns a service with ID $id from the DIC.
     *
     * @param string  $serviceId       - Service ID.
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
     * @param object $service   - Service instance.
     *
     * @return $this
     */
    public function setContainerService($serviceId, $service)
    {
        $this->getContainer()->set($serviceId, $service);

        return $this;
    }

    /**
     * Runs the configuration processors.
     *
     * @throws InvalidConfigException
     *
     * @return void
     */
    protected function runConfigProcessors()
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

        // Now, the onAfterProcess method.

        /** @var ProcessorInterface $processor */
        foreach ($configProcessors as $processor) {
            $processor->onAfterProcess($this, $this->getConfig());
        }
    }

    /**
     * Returns an array of registered config processors.
     *
     * @throws InvalidConfigException
     *
     * @return array
     */
    protected function getConfigProcessors()
    {
        $processors = [];
        $componentsNames = $this->getInstalledComponentsNames();

        foreach ($componentsNames as $componentName) {
            if (!$this->hasComponentConfigParam($componentName, 'components.ironedge/kernel.config.processorClass')) {
                continue;
            }

            $processorClass = $this->getComponentConfigParam(
                $componentName,
                'components.ironedge/kernel.config.processorClass'
            );

            if (!is_string($processorClass)) {
                throw InvalidConfigException::create(
                    'Error in configuration of component "'.$componentName.'": '.
                    'Configuration "components.ironedge/kernel.config.processorClass" must be a string. Received: '.
                    print_r($processorClass, true)
                );
            }

            $processor = new $processorClass();

            if (!($processor instanceof ProcessorInterface)) {
                throw InvalidConfigException::create(
                    'Error in configuration of component "'.$componentName.'": '.
                    'Configuration "components.ironedge/kernel.config.processorClass" must be a class of instance '.
                    '"IronEdge\Component\Kernel\Config\ProcessorInterface".'
                );
            }

            $processors[$componentName] = $processor;
        }

        return $processors;
    }

    /**
     * Loads configuration files from the root project.
     *
     * @return void
     */
    protected function loadRootProjectConfigFiles()
    {
        // Finally, we load the root project's config files. We must load them in these specific order.

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
     * Boots the Kernel
     *
     * @return void
     */
    protected function boot()
    {
        $this->initializeDirectories();
        $this->initializeEnvironment();
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
            $this->initializeConfig($refresh);

            $this->_container = new ContainerBuilder();
            $installedComponents = $this->getInstalledComponents();

            foreach ($installedComponents as $componentName => $componentPath) {
                $path = $componentPath.'/frenzy/services.xml';

                if (is_file($path)) {
                    $loader = new XmlFileLoader($this->_container, new FileLocator(dirname($path)));
                    $loader->load('services.xml');
                }
            }

            $this->_container->compile();
        }
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

            if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
                throw CantCreateDirectoryException::create($name, $dir);
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