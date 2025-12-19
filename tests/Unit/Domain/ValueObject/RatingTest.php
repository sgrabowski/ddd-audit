<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\ValueObject;

use Audit\Domain\ValueObject\Rating;
use PHPUnit\Framework\TestCase;

final class RatingTest extends TestCase
{
    public function test_positive_rating_is_positive(): void
    {
        $rating = Rating::Positive;

        $this->assertTrue($rating->isPositive());
        $this->assertFalse($rating->isNegative());
    }

    public function test_negative_rating_is_negative(): void
    {
        $rating = Rating::Negative;

        $this->assertTrue($rating->isNegative());
        $this->assertFalse($rating->isPositive());
    }
}
