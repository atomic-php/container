# Atomic Container

[![PHP Version](https://img.shields.io/badge/php-%5E8.4-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-passing-brightgreen)](tests/)
[![CI](https://github.com/atomic-php/container/actions/workflows/ci.yml/badge.svg)](https://github.com/atomic-php/container/actions)
[![Codecov](https://codecov.io/gh/atomic-php/container/branch/main/graph/badge.svg)](https://codecov.io/gh/atomic-php/container)
[![Packagist](https://img.shields.io/packagist/v/atomic/container)](https://packagist.org/packages/atomic/container)

A blazingly fast, zero‑bloat PSR‑11 container with a compile step for maximum performance. Built with modern PHP features and designed for high‑throughput applications. Follows the same compile‑time optimization philosophy as Atomic HTTP Kernel and Router.

## Performance

Atomic Container delivers exceptional performance via compile‑time preparation and lazy caching:

- 3.2M–3.4M ops/sec — `get()` for instances/singletons/aliases and `has()` hits
- ~1.8M ops/sec — `get()` for non‑shared factory entries
- Compile is fast and scales with definitions

```text
Container Benchmark:
benchGetInstance              : 3,409,676 ops/sec
benchGetSingleton             : 3,419,738 ops/sec
benchGetFactory               : 1,877,929 ops/sec
benchGetAlias                 : 3,254,276 ops/sec
benchHasHit                   : 3,323,349 ops/sec
benchHasMiss                  : 3,132,513 ops/sec
benchCompile5                 :   450,885 ops/sec
benchCompile20                :   139,451 ops/sec
```

Run them locally with `composer benchmark`.

## Features

- Zero‑bloat, PSR‑11 compliant container
- Compile‑time alias resolution for O(1) lookups
- Lazy singleton caching; factories for non‑shared creation
- Immutable compiled container; safe to share everywhere
- Fully type‑safe codebase with Psalm and strict types
- Built‑in benchmarks and comprehensive tests

## Installation

```bash
composer require atomic/container
```

**Requirements:**

- PHP 8.4 or higher
- PSR‑11 `psr/container`

## PSR Compliance

- PSR‑11: Container interface and exception interfaces

## Quick Start

```php
<?php

use Atomic\Container\ContainerBuilder;
use Psr\Container\ContainerInterface;

$builder = new ContainerBuilder();

// 1) Instances/values
$builder->set('config', ['env' => 'prod']);

// 2) Shared service (singleton)
$builder->set('logger', function (ContainerInterface $c) {
    return new Logger($c->get('config'));
}, shared: true);

// 3) Non-shared factory
$builder->factory('uuid', fn () => Ramsey\Uuid\Uuid::uuid7()->toString());

// 4) Alias
$builder->alias(LoggerInterface::class, 'logger');

// Compile once at boot for fast lookups
$container = $builder->compile();

$logger = $container->get(LoggerInterface::class);
$uuid1  = $container->get('uuid');
$uuid2  = $container->get('uuid'); // different
```

## Architecture

### Core Components

```text
┌────────────────────┐    ┌───────────────────────┐
│  ContainerBuilder    │───▶│  CompiledContainer      │
│  (mutable, setup)    │    │  (immutable, runtime)   │
│  instances/factories │    │  O(1) lookup, caching   │
└────────────────────┘    └───────────────────────┘
```

### Resolution Model

1. `alias(id)` → resolve to final target (pre‑resolved at compile)
2. direct instance/value → return as‑is
3. shared factory → lazily create + cache; subsequent calls return same instance
4. non‑shared factory → create a new value each call
5. otherwise → `NotFoundException`

## API Reference

### ContainerBuilder

Mutable registration builder; produces an immutable PSR‑11 container.

```php
final class ContainerBuilder
{
    public function set(string $id, callable|object|scalar|array|null $value, bool $shared = true): void;
    public function factory(string $id, callable $factory): void; // non-shared
    public function alias(string $id, string $targetId): void;
    public function compile(): Psr\Container\ContainerInterface; // optimized container
}
```

### CompiledContainer

High‑performance PSR‑11 container for runtime use.

```php
final class CompiledContainer implements Psr\Container\ContainerInterface
{
    public function get(string $id): mixed; // may throw NotFoundException|ContainerException
    public function has(string $id): bool;
}
```

### Exceptions

- `Atomic\Container\Exceptions\NotFoundException` (implements `Psr\Container\NotFoundExceptionInterface`)
- `Atomic\Container\Exceptions\ContainerException` (implements `Psr\Container\ContainerExceptionInterface`)

## Testing

Run the comprehensive test suite:

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Static analysis & CS
composer psalm
composer cs-check
```

`composer test-coverage` generates `coverage.xml` (Clover) used by CI; the workflow uploads it to Codecov for the badge/report.

## Benchmarks

```bash
composer benchmark
```

Includes:

- `ContainerBenchmark` — get()/has() performance for instances, singletons, factories, aliases
- `ContainerCompileBenchmark` — compile time with small/medium registration sets

## Notes

- Immutable runtime container: build everything up front via the builder, then share the compiled instance.
- Aliases are cycle‑checked during compilation; self‑aliasing and cycles throw.
- The container is intentionally lean and unopinionated; add higher‑level features (e.g., providers/autowiring) in your app layer if desired.
