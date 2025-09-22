<?php

declare(strict_types=1);

namespace Tests\Integration;

use Atomic\Container\ContainerBuilder;
use Atomic\Container\Exceptions\ContainerException;
use Atomic\Container\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class ContainerIntegrationTest extends TestCase
{
    public function test_compiled_container_provides_dependencies_to_factories(): void
    {
        $builder = new ContainerBuilder();

        // Register configuration as instance
        $config = (object) ['env' => 'prod'];
        $builder->set('config', $config);

        // Register a shared Logger that depends on config
        $builder->set(Logger::class, function (ContainerInterface $c) {
            /** @var object $cfg */
            $cfg = $c->get('config');
            return new Logger($cfg);
        }, shared: true);

        // Non-shared UUID factory
        $builder->factory('uuid', fn () => uniqid('u', true));

        // Alias interface name to concrete logger
        $builder->alias(LoggerInterface::class, Logger::class);

        $container = $builder->compile();

        $logger1 = $container->get(LoggerInterface::class);
        $logger2 = $container->get(Logger::class);
        $this->assertInstanceOf(Logger::class, $logger1);
        $this->assertSame($logger1, $logger2, 'Shared factory returns the same instance');
        $this->assertSame($config, $logger1->config, 'Factory received dependency from container');

        $uuid1 = $container->get('uuid');
        /** @var string $uuid2 */
        $uuid2 = $container->get('uuid');
        $this->assertIsString($uuid1);
        $this->assertNotSame($uuid1, $uuid2, 'Non-shared factory returns new value');
    }

    public function test_alias_chain_resolves_to_shared_singleton(): void
    {
        $b = new ContainerBuilder();
        $b->set('core', fn () => new \stdClass(), shared: true);
        $b->alias('svc', 'core');
        $b->alias('service', 'svc'); // chain: service -> svc -> core

        $c = $b->compile();
        /** @var object $a */
        $a = $c->get('service');
        /** @var object $b2 */
        $b2 = $c->get('svc');
        /** @var object $c2 */
        $c2 = $c->get('core');
        $this->assertSame($a, $b2);
        $this->assertSame($b2, $c2);
    }

    public function test_missing_dependency_inside_factory_is_wrapped_with_container_exception(): void
    {
        $b = new ContainerBuilder();
        $b->set('outer', function (ContainerInterface $c) {
            // This will throw NotFoundException from inner get
            return new \ArrayObject([$c->get('missing')]);
        }, shared: true);

        $c = $b->compile();

        try {
            $c->get('outer');
            $this->fail('Expected ContainerException due to missing dependency');
        } catch (ContainerException $e) {
            $this->assertInstanceOf(NotFoundException::class, $e->getPrevious());
        }
    }

    public function test_container_is_immutable_after_compile(): void
    {
        $b = new ContainerBuilder();
        $b->set('value', 123);
        $c = $b->compile();

        $this->assertTrue($c->has('value'));
        $this->assertSame(123, $c->get('value'));

        // Mutate builder after compile; compiled container should be unaffected
        $b->set('value', 456);
        $b->set('new', 999);

        $this->assertSame(123, $c->get('value'));
        $this->assertFalse($c->has('new'));
    }
}

interface LoggerInterface
{
}

final class Logger implements LoggerInterface
{
    public function __construct(public object $config)
    {
    }
}
