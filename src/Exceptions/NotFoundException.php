<?php

declare(strict_types=1);

namespace Atomic\Container\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

final class NotFoundException extends \RuntimeException implements NotFoundExceptionInterface
{
}
