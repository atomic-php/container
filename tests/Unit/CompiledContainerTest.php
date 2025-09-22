<?php

declare(strict_types=1);

namespace Tests\Unit;

use Atomic\Container\ContainerBuilder;
use Atomic\Container\Exceptions\ContainerException;
use Atomic\Container\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class CompiledContainerTest extends TestCase
{
    public function test_has_checks_instances_shared_and_factories_and_aliases(): void
    {
        $b = new ContainerBuilder();
        $b->set('instance', new \stdClass());
        $b->set('shared', fn () => new \stdClass(), shared: true);
        $b->factory('factory', fn () => new \stdClass());
        $b->alias('aliasInst', 'instance');
        $b->alias('aliasShared', 'shared');
        $b->alias('aliasFactory', 'factory');

        $c = $b->compile();
        $this->assertTrue($c->has('instance'));
        $this->assertTrue($c->has('shared'));
        $this->assertTrue($c->has('factory'));
        $this->assertTrue($c->has('aliasInst'));
        $this->assertTrue($c->has('aliasShared'));
        $this->assertTrue($c->has('aliasFactory'));
        $this->assertFalse($c->has('missing'));
    }

    public function test_get_on_missing_id_throws_not_found(): void
    {
        $c = (new ContainerBuilder())->compile();
        $this->expectException(NotFoundException::class);
        $c->get('nope');
    }

    public function test_shared_factory_is_cached_after_first_resolution(): void
    {
        $b = new ContainerBuilder();
        $calls = 0;
        $b->set('svc', function () use (&$calls) {
            $calls++;
            return new \stdClass();
        }, shared: true);
        $c = $b->compile();

        /** @var \stdClass $a */
        $a = $c->get('svc');
        /** @var \stdClass $b2 */
        $b2 = $c->get('svc');
        $this->assertSame($a, $b2);
        $this->assertSame(1, $calls, 'shared factory called once');
    }

    public function test_factory_can_depend_on_other_entries(): void
    {
        $b = new ContainerBuilder();
        $b->set('config', ['name' => 'app']);
        $b->factory('service', function (ContainerInterface $c) {
            /** @var array{name:string} $cfg */
            $cfg = $c->get('config');
            return new class ($cfg) {
                public function __construct(public array $cfg)
                {
                }
            };
        });
        $c = $b->compile();

        /** @var object $s1 */
        $s1 = $c->get('service');
        /** @var object $s2 */
        $s2 = $c->get('service');
        $this->assertNotSame($s1, $s2);
        $this->assertSame(['name' => 'app'], $s1->cfg);
    }

    public function test_exception_in_shared_factory_is_wrapped_and_preserves_previous(): void
    {
        $b = new ContainerBuilder();
        $b->set('svc', function (ContainerInterface $c) {
            throw new \RuntimeException('boom');
        }, shared: true);
        $c = $b->compile();

        try {
            $c->get('svc');
            $this->fail('Expected ContainerException');
        } catch (ContainerException $e) {
            $this->assertInstanceOf(\RuntimeException::class, $e->getPrevious());
            $this->assertStringContainsString('svc', $e->getMessage());
        }
    }
}
