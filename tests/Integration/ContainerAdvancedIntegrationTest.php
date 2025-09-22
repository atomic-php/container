<?php

declare(strict_types=1);

namespace Tests\Integration;

use Atomic\Container\ContainerBuilder;
use Atomic\Container\Exceptions\ContainerException;
use PHPUnit\Framework\TestCase;

final class ContainerAdvancedIntegrationTest extends TestCase
{
    public function test_alias_to_factory_returns_new_instance_each_call(): void
    {
        $b = new ContainerBuilder();
        $b->factory('factory', fn () => new \stdClass());
        $b->alias('alias', 'factory');
        $c = $b->compile();

        /** @var \stdClass $a */
        $a = $c->get('alias');
        /** @var \stdClass $b2 */
        $b2 = $c->get('alias');
        $this->assertNotSame($a, $b2);
    }

    public function test_alias_to_instance_returns_same_instance(): void
    {
        $inst = new \stdClass();
        $b = new ContainerBuilder();
        $b->set('instance', $inst);
        $b->alias('alias', 'instance');
        $c = $b->compile();

        $this->assertSame($inst, $c->get('alias'));
    }

    public function test_recompile_produces_independent_containers(): void
    {
        $b = new ContainerBuilder();
        $b->set('v', 1);
        $c1 = $b->compile();

        // mutate builder after first compile
        $b->set('v', 2);
        $b->set('new', 123);
        $c2 = $b->compile();

        $this->assertSame(1, $c1->get('v'));
        $this->assertFalse($c1->has('new'));
        $this->assertSame(2, $c2->get('v'));
        $this->assertTrue($c2->has('new'));
    }

    public function test_non_shared_factory_exception_is_wrapped(): void
    {
        $b = new ContainerBuilder();
        $b->factory('x', function () {
            throw new \RuntimeException('bad factory');
        });
        $c = $b->compile();

        $this->expectException(ContainerException::class);
        $c->get('x');
    }

    public function test_deep_alias_chain_resolves_correctly(): void
    {
        $shared = new \stdClass();
        $b = new ContainerBuilder();
        $b->set('target', $shared);
        $b->alias('a', 'target');
        $b->alias('b', 'a');
        $b->alias('c', 'b');
        $c = $b->compile();

        $this->assertSame($shared, $c->get('c'));
        $this->assertTrue($c->has('c'));
    }
}
