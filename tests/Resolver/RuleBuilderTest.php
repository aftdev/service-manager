<?php

namespace AftDevTest\ServiceManager\Resolver;

use AftDev\ServiceManager\Resolver;
use AftDev\ServiceManager\Resolver\RuleBuilder;
use AftDev\Test\TestCase;

/**
 * @internal
 * @covers \AftDev\ServiceManager\Resolver\RuleBuilder
 */
class RuleBuilderTest extends TestCase
{
    /**
     * @dataProvider ruleDataProvider
     *
     * @param mixed $value
     */
    public function testRule(string $type, $value)
    {
        $resolver = $this->prophesize(Resolver::class);
        $ruleBuilder = new RuleBuilder('testService', $resolver->reveal());

        $resolver
            ->addServiceRule('testService', $type, $value)
            ->shouldBeCalledOnce()
        ;

        $ruleBuilder->needs($type)->give($value);
    }

    public function ruleDataProvider()
    {
        return [
            'string' => ['string', 'string'],
            'int' => ['int', 1],
            'float' => ['float', 4.2],
            'object' => ['object', new \stdClass()],
            'callable' => [
                'callable',
                function () {
                    return 'callable';
                },
            ],
        ];
    }
}
