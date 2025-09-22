<?php

declare(strict_types=1);

namespace Benchmarks;

use Atomic\Container\ContainerBuilder;

final class ContainerCompileBenchmark implements BenchmarkInterface
{
    public function benchCompile5(): void
    {
        $b = new ContainerBuilder();
        $b->set('a', new \stdClass());
        $b->set('b', fn () => new \stdClass(), shared: true);
        $b->factory('c', fn () => new \stdClass());
        $b->alias('d', 'b');
        $b->set('e', ['x' => 1]);
        $b->compile();
    }

    public function benchCompile20(): void
    {
        $b = new ContainerBuilder();
        $b->set('config', ['env' => 'prod']);
        for ($i = 0; $i < 10; $i++) {
            $b->set('inst'.$i, new \stdClass());
        }
        for ($i = 0; $i < 5; $i++) {
            $b->set('shared'.$i, fn () => new \stdClass(), shared: true);
        }
        for ($i = 0; $i < 3; $i++) {
            $b->factory('f'.$i, fn () => new \stdClass());
        }
        $b->alias('svc', 'shared0');
        $b->compile();
    }
}

