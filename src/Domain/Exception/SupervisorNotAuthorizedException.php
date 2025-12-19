<?php

declare(strict_types=1);

namespace Audit\Domain\Exception;

use DomainException;

final class SupervisorNotAuthorizedException extends DomainException
{
    public static function forStandard(string $supervisorId, string $standardName): self
    {
        return new self(
            sprintf(
                'Supervisor %s is not authorized for standard %s',
                $supervisorId,
                $standardName
            )
        );
    }
}
