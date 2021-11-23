<?php

namespace AftDevTest\ServiceManager;

use AftDev\ServiceManager\Resolver;
use AftDev\Test\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;

/**
 * @internal
 * @covers \AftDev\ServiceManager\Resolver
 */
class ResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy
     */
    protected $container;

    /**
     * @var Resolver
     */
    protected $resolver;

    public function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);

        $this->resolver = new Resolver($this->container->reveal());
    }

    public function testResolveClass()
    {
        $this->container->has(Argument::any())
            ->will(function ($params) {
                if (ExistingServiceB::class === $params[0]) {
                    return true;
                }

                return false;
            })
        ;

        $serviceB = new ExistingServiceB();
        $this->container
            ->get(ExistingServiceB::class)
            ->willReturn($serviceB)
        ;

        $options = [
            'optionsA' => 'A',
            'optionsB' => ['a', 'b', 'c'],
            'optionsC' => 1,
            'optionsD' => 'mooh2',
            'optionCallable' => function () {
                return 'fromCallable';
            },
        ];

        $class = $this->resolver->resolveClass(TestService::class, $options);

        $this->assertInstanceOf(TestService::class, $class);

        $this->assertSame($serviceB, $class->serviceB);
        $this->assertSame($options['optionsA'], $class->optionsA);
        $this->assertSame($options['optionsB'], $class->optionsB);
        $this->assertSame($options['optionsC'], $class->optionsC);
        $this->assertSame($options['optionsD'], $class->optionsD);
        $this->assertSame('fromCallable', $class->optionCallable);
    }

    /**
     * Test creation when no constructor or no parameters.
     */
    public function testNoConstructorAndConstructorWithoutParams()
    {
        $options = [];

        $service = $this->resolver->resolveClass(ExistingServiceB::class, $options);
        $this->assertInstanceOf(ExistingServiceB::class, $service);

        $serviceNoParams = $this->resolver->resolveClass(ExistingServiceC::class, $options);
        $this->assertInstanceOf(ExistingServiceC::class, $serviceNoParams);
    }

    public function testCallFunction()
    {
        $serviceC = new ExistingServiceC();
        $this->container->has(ExistingServiceC::class)->willReturn(true);
        $this->container
            ->get(ExistingServiceC::class)
            ->willReturn($serviceC)
        ;

        $testClass = $this->resolver->resolveClass(ExistingServiceB::class);

        $returnValue = $this->resolver->call([$testClass, 'handle'], ['options' => 'A']);

        $this->assertSame([
            'service' => $serviceC,
            'options' => 'A',
            'default' => 'default',
        ], $returnValue);

        $function = function (ExistingServiceC $service, string $options, string $default = 'default') {
            return [
                'service' => $service,
                'options' => $options,
                'default' => $default,
            ];
        };

        $returnValue = $this->resolver->call($function, ['options' => 'B']);
        $this->assertSame([
            'service' => $serviceC,
            'options' => 'B',
            'default' => 'default',
        ], $returnValue);

        // Fancy '@' notation to resolve class and function dependencies at the same time.
        $returnValue2 = $this->resolver->call(ExistingServiceB::class.'@handle', ['options' => '@@']);
        $this->assertSame([
            'service' => $serviceC,
            'options' => '@@',
            'default' => 'default',
        ], $returnValue2);

        $returnValue3 = $this->resolver->call(ExistingServiceC::class, ['test' => 'A']);
        $this->assertSame([
            'test' => 'A',
        ], $returnValue3);
    }

    public function testWhen()
    {
        $serviceB = new ExistingServiceB();
        $serviceC = new ExistingServiceC();

        $this->resolver->when(TestService::class)->needs('serviceB')->give($serviceB);
        $this->resolver->when(TestService::class)->needs('optionsA')->give('options');
        $this->resolver->when(TestService::class)->needs('optionsB')->give(['a', 'b']);
        $this->resolver->when(TestService::class)->needs('optionsC')->give(1);
        $this->resolver->when(TestService::class)->needs('optionCallable')->give(function () {
            return 'callable';
        });

        $this->resolver->when(ExistingServiceB::class)->needs('serviceC')->give($serviceC);

        $testClass = $this->resolver->resolveClass(TestService::class);

        $this->assertSame($serviceB, $testClass->serviceB);
        $this->assertSame('options', $testClass->optionsA);
        $this->assertSame(['a', 'b'], $testClass->optionsB);
        $this->assertSame(1, $testClass->optionsC);
        $this->assertSame('callable', $testClass->optionCallable);
    }

    /**
     * Test that the resolver will throw an exception.
     */
    public function testUnknownDependency()
    {
        $this->expectException(\ReflectionException::class);
        $this->resolver->resolveClass(NotAutodiscoverable::class);
    }
}

class TestService
{
    public $serviceB;
    public $optionsA;
    public $optionsB;
    public $optionsC;
    public $optionsD;
    public $optionCallable;

    public function __construct(
        ExistingServiceB $serviceB,
        string $optionsA,
        array $optionsB,
        int $optionsC,
        string $optionsD = 'mooh',
        $optionCallable = null
    ) {
        $this->serviceB = $serviceB;
        $this->optionsA = $optionsA;
        $this->optionsB = $optionsB;
        $this->optionsC = $optionsC;
        $this->optionsD = $optionsD;
        $this->optionCallable = $optionCallable;
    }
}

class ExistingServiceB
{
    public function handle(ExistingServiceC $service, string $options, string $default = 'default')
    {
        return [
            'service' => $service,
            'options' => $options,
            'default' => $default,
        ];
    }
}

class ExistingServiceC
{
    public function __construct()
    {
    }

    public function __invoke(string $test)
    {
        return [
            'test' => $test,
        ];
    }
}

class NotAutodiscoverable
{
    public function __construct(array $test)
    {
    }
}
