<?php

namespace AftDev\ServiceManager\Resolver;

use AftDev\ServiceManager\Resolver;
use Psr\Container\ContainerInterface;

class ResolverFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new Resolver($container);
    }
}
