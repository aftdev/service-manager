<?php

namespace AftDev\ServiceManager;

use Laminas\ServiceManager\AbstractPluginManager as LaminasPluginManager;
use Laminas\ServiceManager\Exception as ServiceManagerException;
use Laminas\Stdlib\ArrayUtils;

abstract class AbstractManager extends LaminasPluginManager
{
    protected $sharedByDefault = true;

    /**
     * Default plugin.
     *
     * @var string
     */
    protected $default;

    /**
     * Options to be applied to all plugins.
     *
     * @var array
     */
    protected $defaultOptions = [];

    /**
     * Plugins that were already initialized.
     *
     * @var array
     */
    protected $plugins = [];

    /**
     * Array containing each plugin options/config.
     *
     * @var array
     */
    protected $pluginsOptions = [];

    /**
     * {@inheritdoc}
     */
    public function configure(array $config)
    {
        parent::configure($config);

        if (isset($config['plugins'])) {
            $this->setPluginOptions($config['plugins']);
        }

        if (isset($config['default'])) {
            $this->setDefault($config['default']);
        }

        if (isset($config['default_options'])) {
            $this->setDefaultOptions($config['default_options']);
        }

        return $this;
    }

    /**
     * Set the default plugin to be used by this manager.
     *
     * @throws ServiceManagerException\ServiceNotFoundException If $default does match any plugin configuration.
     */
    public function setDefault(string $default)
    {
        if (!$this->hasPlugin($default)) {
            throw new ServiceManagerException\ServiceNotFoundException(sprintf(
                '%s service was not found in the plugins configuration',
                $default
            ));
        }

        $this->default = $default;
    }

    /**
     * Get the plugin.
     *
     * If $name is configured
     *
     * {@inheritdoc}
     */
    public function get($name, array $options = null)
    {
        if (!$options && $this->hasPlugin($name)) {
            return $this->getPlugin($name);
        }

        // Default to the Laminas plugin manager get function
        return parent::get($name, $options);
    }

    /**
     * Return true if the manager can create the service.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return parent::has($name) || $this->hasPlugin($name);
    }

    /**
     * Check if the plugin exists.
     */
    public function hasPlugin(string $name): bool
    {
        return array_key_exists($name, $this->pluginsOptions);
    }

    /**
     * Get plugin by name.
     *
     * Configuration option will be fetched from the plugins options.
     *
     * @throws ServiceManagerException\ServiceNotFoundException if the manager does not have a service definition for
     *  the instance, and the service is not auto-invokable.
     * @throws ServiceManagerException\InvalidServiceException if the plugin created is invalid
     *  for the plugin context.
     *
     * @return mixed
     */
    public function getPlugin(string $name)
    {
        if (!isset($this->plugins[$name])) {
            $options = $this->getPluginOptions($name);

            if ($options['service'] ?? false) {
                $service = $options['service'];
                $serviceOptions = $options['options'] ?? [];
            } else {
                // Short notation - the service name is the config key, the options the config itself.
                $service = $name;
                $serviceOptions = $options;
            }

            $serviceOptions = ArrayUtils::merge($this->defaultOptions, $serviceOptions);

            $plugin = parent::build($service, $serviceOptions);
            $this->validate($plugin);

            $this->plugins[$name] = $plugin;
        }

        return $this->plugins[$name];
    }

    /**
     * Return default.
     *
     * @throws ServiceManagerException\ServiceNotFoundException if the manager does not have
     *     a service definition for the instance, and the service is not
     *     auto-invokable.
     * @throws ServiceManagerException\InvalidServiceException if the plugin created is invalid for the
     *     plugin context.
     *
     * @return mixed
     */
    public function getDefault()
    {
        if (!$this->default || !$this->hasPlugin($this->default)) {
            throw new ServiceManagerException\ServiceNotFoundException(sprintf(
                'Invalid default configured for plugin manager %s',
                get_class($this)
            ));
        }

        return $this->get($this->default);
    }

    /**
     * Get Options for given plugin.
     */
    protected function getPluginOptions(string $name): array
    {
        $options = $this->pluginsOptions[$name] ?? null;
        if (null === $options) {
            throw new ServiceManagerException\ServiceNotFoundException(sprintf(
                'Options for the plugin by the name "%s" was not found in the plugin manager %s',
                $name,
                get_class($this)
            ));
        }

        return (array) $options;
    }

    /**
     * Set plugin options.
     */
    protected function setPluginOptions(array $config)
    {
        $this->pluginsOptions = $config;
    }

    /**
     * Set default options that will be used by all plugins.
     */
    protected function setDefaultOptions(array $defaultOptions)
    {
        $this->defaultOptions = $defaultOptions;
    }
}
