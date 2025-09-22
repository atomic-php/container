<?php

declare(strict_types=1);

namespace Tests\Unit;

use Atomic\Container\ContainerBuilder;
use Atomic\Container\Exceptions\ContainerException;
use Atomic\Container\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class ContainerBuilderTest extends TestCase
{
    public function test_set_instance_and_get(): void
    {
        $builder = new ContainerBuilder();
        $config = ['env' => 'test'];
        $builder->set('config', $config);

        $container = $builder->compile();
        $this->assertTrue($container->has('config'));
        $this->assertSame($config, $container->get('config'));
    }

    public function test_shared_factory_returns_same_instance(): void
    {
        $builder = new ContainerBuilder();
        $builder->set('obj', function (ContainerInterface $c) {
            return new \stdClass();
        }, shared: true);

        $container = $builder->compile();
        $a = $container->get('obj');
        /** @var \stdClass $b */
        $b = $container->get('obj');
        $this->assertInstanceOf(\stdClass::class, $a);
        $this->assertSame($a, $b);
    }

    public function test_factory_returns_new_instance_each_time(): void
    {
        $builder = new ContainerBuilder();
        $builder->factory('obj', function (ContainerInterface $c) {
            return new \stdClass();
        });

        $container = $builder->compile();
        $a = $container->get('obj');
        /** @var \stdClass $b */
        $b = $container->get('obj');
        $this->assertInstanceOf(\stdClass::class, $a);
        $this->assertNotSame($a, $b);
    }

    public function test_non_shared_via_set_behaves_like_factory(): void
    {
        $builder = new ContainerBuilder();
        $builder->set('obj', function (ContainerInterface $c) {
            return new \stdClass();
        }, shared: false);

        $container = $builder->compile();
        /** @var \stdClass $x */
        $x = $container->get('obj');
        /** @var \stdClass $y */
        $y = $container->get('obj');
        $this->assertNotSame($x, $y);
    }

    public function test_alias_resolves_target(): void
    {
        $builder = new ContainerBuilder();
        $builder->set('service', new \stdClass());
        $builder->alias('svc', 'service');

        $container = $builder->compile();
        $this->assertTrue($container->has('svc'));
        $this->assertInstanceOf(\stdClass::class, $container->get('svc'));
        $this->assertSame($container->get('svc'), $container->get('service'));
    }

    public function test_get_throws_not_found_for_unknown_id(): void
    {
        $builder = new ContainerBuilder();
        $container = $builder->compile();
        $this->expectException(NotFoundException::class);
        $container->get('missing');
    }

    public function test_factory_exception_is_wrapped_as_container_exception(): void
    {
        $builder = new ContainerBuilder();
        $builder->factory('boom', fn () => throw new \RuntimeException('bad'));
        $container = $builder->compile();

        $this->expectException(ContainerException::class);
        $container->get('boom');
    }

    public function test_alias_cycle_is_detected(): void
    {
        $builder = new ContainerBuilder();
        $builder->alias('a', 'b');
        $builder->alias('b', 'a');

        $this->expectException(ContainerException::class);
        $builder->compile();
    }

    public function test_alias_cannot_point_to_itself(): void
    {
        $builder = new ContainerBuilder();
        $this->expectException(ContainerException::class);
        $builder->alias('self', 'self');
    }

    public function test_alias_to_missing_target_means_has_is_false(): void
    {
        $builder = new ContainerBuilder();
        $builder->alias('svc', 'missing');
        $container = $builder->compile();
        $this->assertFalse($container->has('svc'));
    }

    public function test_value_types_scalar_array_and_null(): void
    {
        $builder = new ContainerBuilder();
        $builder->set('int', 42);
        $builder->set('string', 'hello');
        $builder->set('float', 3.14);
        $builder->set('bool', true);
        $builder->set('array', ['a' => 1]);
        $builder->set('null', null);

        $c = $builder->compile();
        $this->assertSame(42, $c->get('int'));
        $this->assertSame('hello', $c->get('string'));
        $this->assertSame(3.14, $c->get('float'));
        $this->assertTrue($c->get('bool'));
        $this->assertSame(['a' => 1], $c->get('array'));
        $this->assertNull($c->get('null'));
    }

    public function test_instance_precedence_over_factory(): void
    {
        $builder = new ContainerBuilder();
        $instance = (object) ['x' => 1];
        $builder->set('svc', $instance);
        // Later registering a factory with same id should not override instance at runtime
        $builder->factory('svc', fn () => (object) ['x' => 2]);

        $c = $builder->compile();
        $this->assertSame($instance, $c->get('svc'));
    }
}
