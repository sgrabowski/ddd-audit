<?php

declare(strict_types=1);

namespace Audit\Domain\Exception;

use DomainException;

final class AlreadySuspendedException extends DomainException
{
    public static function create(): self
    {
        return new self('Evaluation is already suspended');
    }
}
