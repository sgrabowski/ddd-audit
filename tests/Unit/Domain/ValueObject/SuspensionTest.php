<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\ValueObject;

use Audit\Domain\ValueObject\Suspension;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SuspensionTest extends TestCase
{
    public function test_captures_suspension_timestamp(): void
    {
        $suspendedAt = new DateTimeImmutable('2024-03-15 10:30:00');

        $suspension = new Suspension($suspendedAt);

        $this->assertSame($suspendedAt, $suspension->suspendedAt);
    }
}
