<?php

declare(strict_types=1);

namespace Benchmarks;

use ReflectionClass;

final class BenchmarkRunner
{
    /** @var BenchmarkInterface[] */
    protected array $benchmarks = [];

    public function register(BenchmarkInterface $benchmark): void
    {
        $this->benchmarks[] = $benchmark;
    }

    /**
     * @return array<string,array<string,array{ops_per_sec:float,time_per_op:float,iterations:int}>>
     */
    public function runAll(): array
    {
        $results = [];
        foreach ($this->benchmarks as $benchmark) {
            $name = $this->getBenchmarkName($benchmark);
            $results[$name] = $this->runBenchmark($benchmark);
        }

        return $results;
    }

    /**
     * @return array<string,array{ops_per_sec:float,time_per_op:float,iterations:int}>
     */
    protected function runBenchmark(BenchmarkInterface $benchmark): array
    {
        $ref = new ReflectionClass($benchmark);
        $results = [];

        if (method_exists($benchmark, 'setUp')) {
            $benchmark->setUp();
        }

        foreach ($ref->getMethods() as $method) {
            $name = $method->getName();
            if (!str_starts_with($name, 'bench')) {
                continue;
            }
            $results[$name] = $this->measureMethod($benchmark, $name);
        }

        if (method_exists($benchmark, 'tearDown')) {
            $benchmark->tearDown();
        }

        return $results;
    }

    /**
     * @return array{ops_per_sec:float,time_per_op:float,iterations:int}
     */
    protected function measureMethod(object $benchmark, string $method): array
    {
        $iterations = 100000;

        // Warmup
        $benchmark->$method();

        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $benchmark->$method();
        }
        $elapsed = (hrtime(true) - $start) / 1_000_000_000; // seconds

        return [
            'ops_per_sec' => $iterations / max(1e-9, $elapsed),
            'time_per_op' => $elapsed / $iterations,
            'iterations' => $iterations,
        ];
    }

    protected function getBenchmarkName(BenchmarkInterface $benchmark): string
    {
        $class = get_class($benchmark);
        $parts = explode('\\', $class);
        return (string) array_pop($parts);
    }
}

