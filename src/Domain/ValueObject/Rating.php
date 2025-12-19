<?php

declare(strict_types=1);

namespace Audit\Domain\ValueObject;

enum Rating
{
    case Positive;
    case Negative;

    public function isPositive(): bool
    {
        return $this === self::Positive;
    }

    public function isNegative(): bool
    {
        return $this === self::Negative;
    }
}
