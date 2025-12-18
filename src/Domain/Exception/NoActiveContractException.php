<?php

declare(strict_types=1);

namespace Audit\Domain\Exception;

use DomainException;

final class NoActiveContractException extends DomainException
{
    public static function between(string $clientId, string $supervisorId): self
    {
        return new self(
            sprintf(
                'No active contract between client %s and supervisor %s',
                $clientId,
                $supervisorId
            )
        );
    }
}
