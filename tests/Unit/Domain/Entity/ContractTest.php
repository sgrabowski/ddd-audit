<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\Entity;

use Audit\Domain\Entity\Contract;
use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\ContractId;
use Audit\Domain\ValueObject\SupervisorId;
use PHPUnit\Framework\TestCase;

final class ContractTest extends TestCase
{
    public function test_can_create_active_contract(): void
    {
        $id = ContractId::generate();
        $clientId = ClientId::generate();
        $supervisorId = SupervisorId::generate();

        $contract = new Contract($id, $clientId, $supervisorId, true);

        $this->assertTrue($contract->getId()->equals($id));
        $this->assertTrue($contract->getClientId()->equals($clientId));
        $this->assertTrue($contract->getSupervisorId()->equals($supervisorId));
        $this->assertTrue($contract->isActive());
    }

    public function test_can_create_inactive_contract(): void
    {
        $contract = new Contract(
            ContractId::generate(),
            ClientId::generate(),
            SupervisorId::generate(),
            false
        );

        $this->assertFalse($contract->isActive());
    }
}
