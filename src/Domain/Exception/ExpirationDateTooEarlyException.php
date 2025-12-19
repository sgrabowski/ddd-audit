<?php

declare(strict_types=1);

namespace Audit\Domain\Exception;

use DomainException;

final class ExpirationDateTooEarlyException extends DomainException
{
    public static function mustBeAtLeast180DaysFromAudit(): self
    {
        return new self('Expiration date must be at least 180 days from audit date');
    }
}
