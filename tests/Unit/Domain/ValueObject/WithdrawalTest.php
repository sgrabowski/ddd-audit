<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\ValueObject;

use Audit\Domain\ValueObject\Withdrawal;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class WithdrawalTest extends TestCase
{
    public function test_captures_withdrawal_timestamp(): void
    {
        $withdrawnAt = new DateTimeImmutable('2024-03-20 14:15:00');

        $withdrawal = new Withdrawal($withdrawnAt);

        $this->assertSame($withdrawnAt, $withdrawal->withdrawnAt);
    }
}
