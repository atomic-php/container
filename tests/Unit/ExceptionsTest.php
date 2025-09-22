<?php

declare(strict_types=1);

namespace Tests\Unit;

use Atomic\Container\Exceptions\ContainerException;
use Atomic\Container\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class ExceptionsTest extends TestCase
{
    public function test_not_found_exception_implements_psr_interface(): void
    {
        $e = new NotFoundException('x');
        $this->assertInstanceOf(NotFoundExceptionInterface::class, $e);
        $this->assertSame('x', $e->getMessage());
    }

    public function test_container_exception_implements_psr_interface(): void
    {
        $prev = new \RuntimeException('prev');
        $e = new ContainerException('err', 0, $prev);
        $this->assertInstanceOf(ContainerExceptionInterface::class, $e);
        $this->assertSame('err', $e->getMessage());
        $this->assertSame($prev, $e->getPrevious());
    }
}
