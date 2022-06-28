<?php

namespace AftDev\ServiceManager;

use AftDev\ServiceManager\Resolver\RuleBuilder;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

class Resolver
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Array of rules on how to resolve parameters.
     *
     * @var array
     */
    protected $rules;

    /**
     * Resolver constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Create a class with all dependencies automatically injected.
     *
     * @throws ReflectionException If a parameter cannot be resolved.
     */
    public function resolveClass(string $requestedName, array $parameters = []): object
    {
        $reflectionClass = new ReflectionClass($requestedName);

        if (null === ($constructor = $reflectionClass->getConstructor())) {
            return new $requestedName();
        }

        $reflectionParameters = $constructor->getParameters();

        if (empty($reflectionParameters)) {
            return new $requestedName();
        }

        $constructorParameters = array_map(function (ReflectionParameter $parameter) use ($requestedName, $parameters) {
            $parameterName = $parameter->getName();
            if (array_key_exists($parameterName, $parameters)) {
                return $this->getParameterValue($parameters[$parameterName]);
            }

            return $this->getServiceParameter($requestedName, $parameter);
        }, $reflectionParameters);

        return new $requestedName(...$constructorParameters);
    }

    /**
     * Automatically call a function with all parameters injected from the container.
     *
     * @param array|callable|string $function - The function name or an array class,function name.
     * @param array $parameters - List of Hard coded values.
     *
     * @throws ReflectionException If a parameter cannot be resolved.
     */
    public function call($function, array $parameters = [])
    {
        // Check if callable
        if (is_callable($function)) {
            if (is_array($function)) {
                $reflection = new ReflectionMethod(...$function);
            } else {
                $reflection = new ReflectionFunction($function);
            }
        } else {
            $exploded = explode('@', $function);

            $className = $exploded[0];
            $functionName = $exploded[1] ?? '__invoke';

            $reflection = new ReflectionMethod($className, $functionName);
            $function = [$this->resolveClass($className, $parameters), $functionName];
        }

        $reflectionParameters = $reflection->getParameters();

        $parameters = array_map(function (ReflectionParameter $parameter) use ($parameters) {
            $parameterName = $parameter->getName();
            if (array_key_exists($parameterName, $parameters)) {
                return $this->getParameterValue($parameters[$parameterName]);
            }

            return $this->resolveParameter($parameter);
        }, $reflectionParameters);

        return call_user_func($function, ...$parameters);
    }

    /**
     * Create Rule builder for a service.
     */
    public function when(string $serviceName): RuleBuilder
    {
        return new RuleBuilder($serviceName, $this);
    }

    /**
     * Add a default value for a parameter.
     *
     * @param mixed $implementation
     */
    public function addServiceRule(string $serviceName, string $parameter, $implementation)
    {
        $this->rules[$serviceName][$parameter] = $implementation;
    }

    /**
     * Get value for a service parameter.
     *
     * @throws ReflectionException If parameter cannot be resolved.
     *
     * @return mixed
     */
    protected function getServiceParameter(string $serviceName, ReflectionParameter $parameter)
    {
        $parameterName = $parameter->getName();
        if (isset($this->rules[$serviceName]) && array_key_exists($parameterName, $this->rules[$serviceName])) {
            return $this->getParameterValue($this->rules[$serviceName][$parameterName]);
        }

        return $this->resolveParameter($parameter);
    }

    protected function getParameterValue($value)
    {
        return $value instanceof \Closure ? $value() : $value;
    }

    /**
     * @throws ReflectionException If parameter cannot be resolved.
     */
    protected function resolveParameter(ReflectionParameter $parameter)
    {
        $parameterName = $parameter->getName();

        // Check that we have the value in the container.
        $type = $parameter->getType() ?? null;
        $notBuildIn = $type && !$type->isBuiltin();
        if ($type && $notBuildIn && $this->container->has($type->getName())) {
            return $this->container->get($type->getName());
        }

        try {
            // Finally check for default value.
            return $parameter->getDefaultValue();
        } catch (ReflectionException $e) {
            throw new ReflectionException(
                sprintf(
                    'Unable to resolve parameter "%s"',
                    $parameterName
                ),
                0,
                $e
            );
        }
    }
}
