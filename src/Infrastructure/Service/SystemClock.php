<?php

declare(strict_types=1);

namespace Audit\Infrastructure\Service;

use Audit\Domain\Service\Clock;
use DateTimeImmutable;

final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
