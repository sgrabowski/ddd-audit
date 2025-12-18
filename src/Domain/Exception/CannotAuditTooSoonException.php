<?php

declare(strict_types=1);

namespace Audit\Domain\Exception;

use DomainException;

final class CannotAuditTooSoonException extends DomainException
{
    public static function afterPositive(int $daysRequired, int $daysPassed): self
    {
        return new self(
            sprintf(
                'Cannot audit within 180 days of prior positive evaluation (required: %d days, passed: %d days)',
                $daysRequired,
                $daysPassed
            )
        );
    }

    public static function afterNegative(int $daysRequired, int $daysPassed): self
    {
        return new self(
            sprintf(
                'Cannot audit within 30 days of prior negative evaluation (required: %d days, passed: %d days)',
                $daysRequired,
                $daysPassed
            )
        );
    }
}
