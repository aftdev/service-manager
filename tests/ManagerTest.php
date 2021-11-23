<?php

namespace AftDevTest\ServiceManager;

use AftDev\ServiceManager\AbstractManager;
use AftDev\Test\TestCase;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 * @covers \AftDev\ServiceManager\AbstractManager
 */
class ManagerTest extends TestCase
{
    /**
     * @var ServiceManager
     */
    protected $container;

    /**
     * @var AbstractManager
     */
    protected $manager;

    /**
     * @var MockObject
     */
    protected $serviceFactory;

    public function setUp(): void
    {
        $this->container = new ServiceManager([]);

        $this->serviceFactory = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock()
        ;

        $pluginManagerConfig = [
            'default' => 'adapter_2',
            'default_options' => [
                'default_option' => 'test_default',
                'option_1' => 'option 1 default',
            ],
            'plugins' => [
                'adapter_1' => [
                    'service' => 'service_via_factory_a',
                    'options' => [
                        'option_1' => 'Option 1',
                    ],
                ],
                'adapter_2' => [
                    'service' => 'service_via_factory_b',
                    'options' => [
                        'option_1' => 'Option 2',
                    ],
                ],
                'short_notation' => [
                    'option_1' => 'Option 3',
                ],
                'short_notation_no_options' => [
                ],
                'auto' => [
                    'service' => 'test',
                    'options' => [
                        'option_1' => 'Option 4',
                    ],
                ],
            ],
            'factories' => [
                'service_via_factory_a' => $this->serviceFactory,
                'service_via_factory_b' => $this->serviceFactory,
                'short_notation' => $this->serviceFactory,
                'short_notation_no_options' => $this->serviceFactory,
                'factory_service_not_used_by_plugins' => $this->serviceFactory,
            ],
        ];

        $this->manager = new class($this->container, $pluginManagerConfig) extends AbstractManager {
        };
    }

    /**
     * Test that plugins are created properly with the right options.
     */
    public function testManager()
    {
        $this->serviceFactory->expects($this->exactly(6))
            ->method('__invoke')
            ->withConsecutive(
                [$this->container, 'service_via_factory_a', ['option_1' => 'Option 1', 'default_option' => 'test_default']],
                [$this->container, 'service_via_factory_b', ['option_1' => 'Option 2', 'default_option' => 'test_default']],
                [$this->container, 'short_notation', ['option_1' => 'Option 3', 'default_option' => 'test_default']],
                [$this->container, 'short_notation_no_options', ['option_1' => 'option 1 default', 'default_option' => 'test_default']],
                [$this->container, 'factory_service_not_used_by_plugins'],
                [$this->container, 'factory_service_not_used_by_plugins', ['with' => 'options']]
            )
            ->willReturnOnConsecutiveCalls(
                (object) ['name' => 'A'],
                (object) ['name' => 'B'],
                (object) ['name' => 'C'],
                (object) ['name' => 'D'],
                (object) ['name' => 'E'],
                (object) ['name' => 'F']
            )
        ;

        $test = $this->manager->get('adapter_1');
        $this->assertSame('A', $test->name);

        $test2 = $this->manager->get('adapter_2');
        $this->assertSame('B', $test2->name);

        $test3 = $this->manager->get('short_notation');
        $this->assertSame('C', $test3->name);

        $test4 = $this->manager->get('short_notation_no_options');
        $this->assertSame('D', $test4->name);

        // Test getting a service that is not configured but has factory, (Normal Laminas PluginManager logic)
        $test5 = $this->manager->get('factory_service_not_used_by_plugins');
        $this->assertSame('E', $test5->name);

        $test6 = $this->manager->get('factory_service_not_used_by_plugins', ['with' => 'options']);
        $this->assertSame('F', $test6->name);

        // Services are shared by default - so this should return the same objects.
        $same_object_plugin = $this->manager->get('adapter_2');
        $this->assertSame($test2, $same_object_plugin);

        $same_object_normal_service = $this->manager->get('factory_service_not_used_by_plugins');
        $this->assertSame($test5, $same_object_normal_service);
    }

    public function testDefaultPlugin()
    {
        $this->serviceFactory->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive(
                [$this->container, 'service_via_factory_b', ['option_1' => 'Option 2', 'default_option' => 'test_default']],
                [$this->container, 'service_via_factory_a', ['option_1' => 'Option 1', 'default_option' => 'test_default']]
            )
            ->willReturnOnConsecutiveCalls('defaultB', 'defaultA')
        ;

        $default = $this->manager->getDefault();
        $this->assertSame('defaultB', $default);

        $this->manager->setDefault('adapter_1');

        $default = $this->manager->getDefault();
        $this->assertSame('defaultA', $default);

        // Test unknown default.
        $this->expectException(ServiceNotFoundException::class);
        $this->manager->setDefault('unknown');
    }

    /**
     * Make sure exception is thrown if plugin is unknown.
     */
    public function testUnknownPluginOptionsException()
    {
        $this->expectException(ServiceNotFoundException::class);

        $this->manager->getPlugin('unknown');
    }

    /**
     * Test that exception is thrown if trying to retrieve default plugin.
     */
    public function testNoDefaultValues()
    {
        $this->expectException(ServiceNotFoundException::class);

        $noDefaultManager = new class($this->container, [], []) extends AbstractManager {
        };
        $noDefaultManager->getDefault();
    }

    /**
     * Test hasPlugin function is working properly.
     */
    public function testHasWithPlugins()
    {
        $hasAdapter1 = $this->manager->has('adapter_1');
        $this->assertTrue($hasAdapter1);

        $hasAdapter2 = $this->manager->has('adapter_unknown');
        $this->assertFalse($hasAdapter2);
    }
}
