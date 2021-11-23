# Service Manager

This package contains extra goodies built on top of the [laminas service
manager](https://docs.laminas.dev/laminas-servicemanager/).

## Configurable Service Manager

Extends the Laminas Plugin Manager.
Allow definition of plugin configuration/settings.
To easily create configurable services/plugins.

```php
<?php
$config = [
    'sessions' => [
        'default' => 'adapter_1',
        'default_options' => [
            'option_default' => 'Default',
        ],
        'plugins' => [
            'adapter_1' => [
                'service' => AdapterClass::class,  // What service to use,
                'options' => [
                    'option_1' => 'Option1',
                    'option_2' => 'Option2',
                ],
            ],
            'adapter_2' => [
                'service' => OtherAdapterClass::class,
                'options' => [
                    'option_1' => 'Option1',
                    'option_2' => 'Option2',
                ],
            ],
            // Short notation. (key is the name of the service to use)
            'service_name' => [
                'option_1' => 'Option1',
            ]
        ],
        // Laminas Plugin service manager configuration like factories or aliases.
        'factories' => [
            AdapterClass::class => Invokable::class,
            OtherAdapterClass::class => Invokable::class,
        ],
        'aliases' => [
            'service_name' => AdapterClass::class,
        ],
    ],
];
```

## Service Resolver

Use the Resolver to automatically inject dependencies when calling function.
This is based on the laravel container [functionality](https://laravel.com/docs/8.x/container#method-invocation-and-injection).

```php
<?php
$container = new class implements Psr\Container\ContainerInterface;

$resolver = new Resolver($container);

// With closures.
$resolver->call(function (Dependency $dependency) {
    $dependency->doSomething();
});

// With classes.
class ServiceA
{
    protected $dependencyA;
    function __construct(DependencyA $dependencyA)
    {
        $this->dependencyA = $dependencyA;
    }

    function handle(DependencyB $dependencyB)
    {
        $this->dependencyA->doSomething();
        $dependencyB->doSomething();
    }
}

$serviceA = $resolver->resolveClass(ServiceA::class);
$resolver->call([$serviceA, 'handle']);

// Create service and call handle function shortcut.
$resolver->call(ServiceA::class.'@handle');

```

## Reflection Abstract Factory

Abstract factory that will auto inject dependencies of your services.
Add it to your laminas service manager configuration under

```php
<?php

$service_manager_config = [
    'factories' => [],
    'aliases' => [],
    'abstract_factories' => [
        'default' => ReflectionAbstractFactory::class,
    ],
];

```
