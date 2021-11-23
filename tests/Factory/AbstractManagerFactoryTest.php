<?php

namespace AftDevTest\ServiceManager\Factory;

use AftDev\ServiceManager\AbstractManager;
use AftDev\ServiceManager\Factory\AbstractManagerFactory;
use AftDev\Test\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 * @covers \AftDev\ServiceManager\Factory\AbstractManagerFactory
 */
class AbstractManagerFactoryTest extends TestCase
{
    public function testFactory()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn([
            'test' => [
                'factories' => [
                    'a' => 'b',
                ],
            ],
        ]);

        $factory = new TestFactory();
        $manager = $factory($container->reveal());
        $this->assertTrue($manager->has('a'));

        $factory = new TestFactoryInvalidKey();
        $manager = $factory($container->reveal());

        $this->assertFalse($manager->has('a'));
    }
}

class TestFactory extends AbstractManagerFactory
{
    protected $managerClass = TestServiceManager::class;

    protected $configKey = 'test';
}

class TestFactoryInvalidKey extends AbstractManagerFactory
{
    protected $managerClass = TestServiceManager::class;

    protected $configKey = 'invalid';
}

class TestServiceManager extends AbstractManager
{
}
