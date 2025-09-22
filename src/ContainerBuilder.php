<?php

declare(strict_types=1);

namespace Atomic\Container;

use Atomic\Container\Exceptions\ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Mutable builder for registering services and compiling an immutable container.
 *
 * - set(id, value|factory, shared?): object values are stored directly; callables are factories
 * - factory(id, callable): non-shared factory producing a new value each call
 * - alias(id, targetId): id resolves to targetId
 * - compile(): produce a high-performance, readonly container
 */
final class ContainerBuilder
{
    /** @var array<string, mixed> */
    protected array $instances = [];

    /** @var array<string, callable(ContainerInterface):mixed> */
    protected array $sharedFactories = [];

    /** @var array<string, callable(ContainerInterface):mixed> */
    protected array $factories = [];

    /** @var array<string, string> */
    protected array $aliases = [];

    /**
     * Register a value or factory under an id.
     *
     * @param callable(ContainerInterface):mixed|object|scalar|array|null $value
     */
    public function set(string $id, callable|object|array|string|int|float|bool|null $value, bool $shared = true): void
    {
        if (\is_callable($value)) {
            if ($shared) {
                $this->sharedFactories[$id] = $value;
            } else {
                $this->factories[$id] = $value;
            }
            return;
        }

        // Store direct value/instance
        $this->instances[$id] = $value;
    }

    /**
     * Register a non-shared factory (returns a new value on each get).
     *
     * @param callable(ContainerInterface):mixed $factory
     */
    public function factory(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    /**
     * Create an alias so that $id resolves to the target service id.
     */
    public function alias(string $id, string $targetId): void
    {
        if ($id === $targetId) {
            throw new ContainerException('Alias id and target id must differ');
        }
        $this->aliases[$id] = $targetId;
    }

    /**
     * Compile into an immutable, high-performance PSR-11 container.
     */
    public function compile(): \Psr\Container\ContainerInterface
    {
        // Pre-resolve alias chains to their final target for O(1) lookups
        $resolvedAliases = $this->resolveAliases($this->aliases);

        return new CompiledContainer(
            instances: $this->instances,
            sharedFactories: $this->sharedFactories,
            factories: $this->factories,
            aliases: $resolvedAliases,
        );
    }

    /**
     * Resolve alias chains and detect cycles.
     *
     * @param array<string,string> $aliases
     * @return array<string,string>
     */
    protected function resolveAliases(array $aliases): array
    {
        $resolved = [];
        foreach ($aliases as $id => $target) {
            $seen = [$id => true];
            $cur = $target;
            while (isset($aliases[$cur])) {
                if (isset($seen[$cur])) {
                    throw new ContainerException('Alias cycle detected for "'.$id.'"');
                }
                $seen[$cur] = true;
                $cur = $aliases[$cur];
            }
            $resolved[$id] = $cur;
        }

        return $resolved;
    }
}
