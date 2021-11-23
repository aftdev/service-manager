<?php

namespace AftDev\ServiceManager;

class ConfigProvider
{
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies()
    {
        return [
            'factories' => [
                Resolver::class => Resolver\ResolverFactory::class,
            ],
        ];
    }
}
