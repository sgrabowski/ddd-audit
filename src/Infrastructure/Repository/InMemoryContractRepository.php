<?php

declare(strict_types=1);

namespace Audit\Infrastructure\Repository;

use Audit\Domain\Entity\Contract;
use Audit\Domain\Repository\ContractRepository;
use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\SupervisorId;

final class InMemoryContractRepository implements ContractRepository
{
    /**
     * @param array<Contract> $contracts
     */
    public function __construct(
        private array $contracts = []
    ) {
    }

    public function add(Contract $contract): void
    {
        $this->contracts[] = $contract;
    }

    public function hasActiveContract(ClientId $clientId, SupervisorId $supervisorId): bool
    {
        foreach ($this->contracts as $contract) {
            if ($contract->getClientId()->equals($clientId)
                && $contract->getSupervisorId()->equals($supervisorId)
                && $contract->isActive()
            ) {
                return true;
            }
        }

        return false;
    }
}
