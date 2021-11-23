<?php

namespace AftDevTest\ServiceManager\Factory;

use AftDev\ServiceManager\Factory\ReflectionAbstractFactory;
use AftDev\ServiceManager\Resolver;
use AftDev\Test\TestCase;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerInterface;

/**
 * @internal
 * @covers \AftDev\ServiceManager\Factory\ReflectionAbstractFactory
 * @covers \AftDev\ServiceManager\Resolver
 */
class ReflectionAbstractFactoryTest extends TestCase
{
    public function testFactory()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $resolver = $this->prophesize(Resolver::class);

        $returned = new \stdClass();
        $resolver
            ->resolveClass(PublicConstructor::class, [])
            ->shouldBeCalledOnce()
            ->willReturn($returned)
        ;

        $container->has(Resolver::class)->willReturn(true);
        $container
            ->get(Resolver::class)
            ->willReturn($resolver->reveal())
        ;

        $built = (new ReflectionAbstractFactory())($container->reveal(), PublicConstructor::class, []);

        $this->assertSame($returned, $built);
    }

    /**
     * Test that the factory correctly triggers an error.
     */
    public function testInvalidOption()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $resolver = $this->prophesize(Resolver::class);

        $resolver
            ->resolveClass(PublicConstructor::class, [])
            ->willThrow(new \ReflectionException())
        ;

        $container->has(Resolver::class)->willReturn(true);
        $container
            ->get(Resolver::class)
            ->willReturn($resolver->reveal())
        ;

        $this->expectException(ServiceNotFoundException::class);
        (new ReflectionAbstractFactory())($container->reveal(), PublicConstructor::class);
    }

    /**
     * Test the can create function.
     */
    public function testCanCreate()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $factory = new ReflectionAbstractFactory();

        $canCreate = $factory->canCreate($container->reveal(), PublicConstructor::class);
        $this->assertTrue($canCreate);

        $canCreate = $factory->canCreate($container->reveal(), NoConstructor::class);
        $this->assertTrue($canCreate);

        $canCreate = $factory->canCreate($container->reveal(), ProtectedConstructor::class);
        $this->assertFalse($canCreate);
    }
}

class PublicConstructor
{
    public function __construct()
    {
    }
}

class NoConstructor
{
}

class ProtectedConstructor
{
    protected function __construct()
    {
    }
}
