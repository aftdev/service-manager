<?php

namespace AftDev\ServiceManager\Resolver;

use AftDev\ServiceManager\Resolver;

class RuleBuilder
{
    /**
     * @var string
     */
    protected $serviceName;

    /**
     * @var Resolver
     */
    protected $resolver;

    public function __construct(string $serviceName, Resolver $resolver)
    {
        $this->serviceName = $serviceName;
        $this->resolver = $resolver;
    }

    public function needs(string $parameter): self
    {
        $this->parameter = $parameter;

        return $this;
    }

    public function give($give): self
    {
        if ($this->parameter) {
            $this->resolver->addServiceRule($this->serviceName, $this->parameter, $give);
        }

        return $this;
    }
}
