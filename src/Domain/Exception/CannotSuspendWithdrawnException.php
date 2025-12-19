<?php

declare(strict_types=1);

namespace Audit\Domain\Exception;

use DomainException;

final class CannotSuspendWithdrawnException extends DomainException
{
    public static function create(): self
    {
        return new self('Cannot suspend withdrawn evaluation');
    }
}
