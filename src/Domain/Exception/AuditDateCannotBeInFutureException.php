<?php

declare(strict_types=1);

namespace Audit\Domain\Exception;

use DomainException;

final class AuditDateCannotBeInFutureException extends DomainException
{
    public static function create(): self
    {
        return new self('Audit date cannot be in the future');
    }
}
