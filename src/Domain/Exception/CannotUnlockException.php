<?php

declare(strict_types=1);

namespace Audit\Domain\Exception;

use DomainException;

final class CannotUnlockException extends DomainException
{
    public static function notSuspended(): self
    {
        return new self('Cannot unlock: evaluation is not suspended');
    }
}
