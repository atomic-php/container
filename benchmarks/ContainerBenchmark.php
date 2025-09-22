<?php

declare(strict_types=1);

namespace Benchmarks;

use Atomic\Container\ContainerBuilder;
use Psr\Container\ContainerInterface;

final class ContainerBenchmark implements BenchmarkInterface
{
    private ContainerInterface $container;

    public function setUp(): void
    {
        $b = new ContainerBuilder();
        $b->set('config', ['env' => 'prod']);
        $b->set('instance', new \stdClass());
        $b->set('shared', fn () => new \stdClass(), shared: true);
        $b->factory('factory', fn () => new \stdClass());
        $b->alias('svc', 'shared');
        $this->container = $b->compile();
    }

    public function benchGetInstance(): void
    {
        $this->container->get('instance');
    }

    public function benchGetSingleton(): void
    {
        $this->container->get('shared');
    }

    public function benchGetFactory(): void
    {
        $this->container->get('factory');
    }

    public function benchGetAlias(): void
    {
        $this->container->get('svc');
    }

    public function benchHasHit(): void
    {
        $this->container->has('shared');
    }

    public function benchHasMiss(): void
    {
        $this->container->has('missing');
    }
}
