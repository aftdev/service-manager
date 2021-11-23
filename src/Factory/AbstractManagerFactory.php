<?php

namespace AftDev\ServiceManager\Factory;

use AftDev\ServiceManager\AbstractManager;
use Psr\Container\ContainerInterface;

class AbstractManagerFactory
{
    /**
     * Manager to load.
     *
     * @var string
     */
    protected $managerClass;

    /**
     * Name of key for the manager configuration.
     *
     * @var string
     */
    protected $configKey;

    public function __invoke(ContainerInterface $container): AbstractManager
    {
        $managerConfiguration = $this->getManagerConfiguration($container);

        return $this->getManager($container, $managerConfiguration);
    }

    /**
     * Returns the configuration for the manager.
     */
    public function getManagerConfiguration(ContainerInterface $container): array
    {
        return $this->configKey ? $container->get('config')[$this->configKey] ?? [] : [];
    }

    /**
     * Retrieve the manager based on the configuration.
     */
    protected function getManager(ContainerInterface $container, array $managerConfiguration): AbstractManager
    {
        return new $this->managerClass($container, $managerConfiguration);
    }
}
