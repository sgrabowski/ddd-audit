<?php

declare(strict_types=1);

namespace Audit\Domain\Exception;

use DomainException;

final class CannotLockExpiredException extends DomainException
{
    public static function create(): self
    {
        return new self('Cannot lock expired evaluation');
    }
}
