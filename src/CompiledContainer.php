<?php

declare(strict_types=1);

namespace Atomic\Container;

use Atomic\Container\Exceptions\ContainerException;
use Atomic\Container\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;

/**
 * High-performance PSR-11 container compiled from a ContainerBuilder.
 *
 * - Instances are returned directly
 * - Shared factories are resolved lazily and cached
 * - Non-shared factories produce a new value on each call
 * - Aliases are pre-resolved by the builder to final targets
 */
final class CompiledContainer implements ContainerInterface
{
    /**
     * @param array<string,mixed> $instances
     * @param array<string,callable(ContainerInterface):mixed> $sharedFactories
     * @param array<string,callable(ContainerInterface):mixed> $factories
     * @param array<string,string> $aliases
     */
    public function __construct(
        private array $instances,
        private array $sharedFactories,
        private array $factories,
        private array $aliases,
    ) {
    }

    #[\Override]
    public function get(string $id): mixed
    {
        $id = $this->aliases[$id] ?? $id;

        // 1) direct instance/value
        if (\array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        // 2) shared factory (singleton, cache inside instances after creation)
        if (isset($this->sharedFactories[$id])) {
            try {
                // materialize and cache
                /** @var mixed $value */
                $value = ($this->sharedFactories[$id])($this);
            } catch (\Throwable $e) {
                throw new ContainerException('Error while creating shared service "'.$id.'"', 0, $e);
            }

            // mutate local copy to reflect cache for subsequent calls
            // note: readonly only prevents property reassignment; array mutations are allowed
            $this->instances[$id] = $value;
            // Avoid re-running factory next time
            unset($this->sharedFactories[$id]);

            return $value;
        }

        // 3) non-shared factory (new instance each time)
        if (isset($this->factories[$id])) {
            try {
                return ($this->factories[$id])($this);
            } catch (\Throwable $e) {
                throw new ContainerException('Error while creating service "'.$id.'"', 0, $e);
            }
        }

        // 4) not found
        throw new NotFoundException('No entry found for "'.$id.'"');
    }

    #[\Override]
    public function has(string $id): bool
    {
        $id = $this->aliases[$id] ?? $id;
        return \array_key_exists($id, $this->instances)
            || isset($this->sharedFactories[$id])
            || isset($this->factories[$id]);
    }
}
