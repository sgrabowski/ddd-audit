<?php

declare(strict_types=1);

namespace Audit\Domain\Exception;

use DomainException;

final class ManagerCannotBeWatcherException extends DomainException
{
    public static function create(): self
    {
        return new self('Manager cannot be added as watcher');
    }
}
