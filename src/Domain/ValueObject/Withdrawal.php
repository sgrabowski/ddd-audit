<?php

declare(strict_types=1);

namespace Audit\Domain\ValueObject;

use DateTimeImmutable;

final readonly class Withdrawal
{
    public function __construct(
        public DateTimeImmutable $withdrawnAt,
    ) {
    }
}
