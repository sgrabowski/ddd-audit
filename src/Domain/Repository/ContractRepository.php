<?php

declare(strict_types=1);

namespace Audit\Domain\Repository;

use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\SupervisorId;

interface ContractRepository
{
    public function hasActiveContract(ClientId $clientId, SupervisorId $supervisorId): bool;
}
