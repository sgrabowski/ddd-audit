<?php

declare(strict_types=1);

namespace Audit\Domain\Exception;

use DomainException;

final class OwnerCannotBeWatcherException extends DomainException
{
    public static function create(): self
    {
        return new self('Owner cannot be added as watcher');
    }
}
