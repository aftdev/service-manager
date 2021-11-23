<?php

namespace AftDev\ServiceManager\Factory;

use AftDev\ServiceManager\Resolver;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;

class ReflectionAbstractFactory implements AbstractFactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var Resolver $resolver */
        $resolver = $container->has(Resolver::class) ? $container->get(Resolver::class) : new Resolver($container);

        try {
            return $resolver->resolveClass($requestedName, ($options ?? []));
        } catch (ReflectionException $e) {
            throw new ServiceNotFoundException(
                sprintf(
                    'Unable to create service "%s"; unable to resolve a parameter',
                    $requestedName
                ),
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return class_exists($requestedName) && $this->canCallConstructor($requestedName);
    }

    protected function canCallConstructor($requestedName)
    {
        $constructor = (new ReflectionClass($requestedName))->getConstructor();

        return null === $constructor || $constructor->isPublic();
    }
}
