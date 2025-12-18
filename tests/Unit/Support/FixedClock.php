<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Support;

use Audit\Domain\Service\Clock;
use DateTimeImmutable;

final class FixedClock implements Clock
{
    public function __construct(
        private DateTimeImmutable $fixedTime
    ) {
    }

    public function now(): DateTimeImmutable
    {
        return $this->fixedTime;
    }

    public static function at(string $time): self
    {
        return new self(new DateTimeImmutable($time));
    }
}
