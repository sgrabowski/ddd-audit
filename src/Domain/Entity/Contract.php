<?php

declare(strict_types=1);

namespace Audit\Domain\Entity;

use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\ContractId;
use Audit\Domain\ValueObject\SupervisorId;

final class Contract
{
    public function __construct(
        private readonly ContractId $id,
        private readonly ClientId $clientId,
        private readonly SupervisorId $supervisorId,
        private readonly bool $active,
    ) {
    }

    public function getId(): ContractId
    {
        return $this->id;
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getSupervisorId(): SupervisorId
    {
        return $this->supervisorId;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
