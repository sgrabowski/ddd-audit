<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\Service;

use Audit\Domain\Entity\Client;
use Audit\Domain\Entity\Contract;
use Audit\Domain\Entity\Standard;
use Audit\Domain\Entity\Supervisor;
use Audit\Domain\Exception\NoActiveContractException;
use Audit\Domain\Exception\SupervisorNotAuthorizedException;
use Audit\Domain\Service\RecordEvaluationService;
use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\ContractId;
use Audit\Domain\ValueObject\Rating;
use Audit\Domain\ValueObject\StandardId;
use Audit\Domain\ValueObject\SupervisorId;
use Audit\Infrastructure\Repository\InMemoryContractRepository;
use Audit\Tests\Unit\Support\FixedClock;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class RecordEvaluationServiceTest extends TestCase
{
    public function test_records_evaluation_with_valid_contract_and_authority(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $client = $this->createClient();
        $standard = $this->createStandard();
        $supervisor = $this->createSupervisor([$standard->getId()]);
        $contract = $this->createActiveContract($client->getId(), $supervisor->getId());

        $contractRepo = new InMemoryContractRepository([$contract]);
        $service = new RecordEvaluationService($contractRepo, $clock);

        $evaluation = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            new DateTimeImmutable('2024-05-01'),
            new DateTimeImmutable('2024-11-01')
        );

        $this->assertTrue($evaluation->getOwnerId()->equals($client->getId()));
        $this->assertTrue($evaluation->getManagerId()->equals($supervisor->getId()));
        $this->assertTrue($evaluation->getStandardId()->equals($standard->getId()));
    }

    public function test_rejects_when_no_active_contract(): void
    {
        $this->expectException(NoActiveContractException::class);

        $clock = FixedClock::at('2024-06-01');
        $client = $this->createClient();
        $standard = $this->createStandard();
        $supervisor = $this->createSupervisor([$standard->getId()]);

        $contractRepo = new InMemoryContractRepository([]); // No contracts!
        $service = new RecordEvaluationService($contractRepo, $clock);

        $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            new DateTimeImmutable('2024-05-01'),
            new DateTimeImmutable('2024-11-01')
        );
    }

    public function test_rejects_when_supervisor_lacks_authority(): void
    {
        $this->expectException(SupervisorNotAuthorizedException::class);

        $clock = FixedClock::at('2024-06-01');
        $client = $this->createClient();
        $standard = $this->createStandard();
        $otherStandard = $this->createStandard();
        $supervisor = $this->createSupervisor([$otherStandard->getId()]); // Wrong authority!
        $contract = $this->createActiveContract($client->getId(), $supervisor->getId());

        $contractRepo = new InMemoryContractRepository([$contract]);
        $service = new RecordEvaluationService($contractRepo, $clock);

        $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            new DateTimeImmutable('2024-05-01'),
            new DateTimeImmutable('2024-11-01')
        );
    }

    private function createClient(): Client
    {
        return new Client(ClientId::generate(), 'Test Client');
    }

    private function createStandard(): Standard
    {
        return new Standard(StandardId::generate(), 'ISO 9001');
    }

    /**
     * @param array<StandardId> $authorities
     */
    private function createSupervisor(array $authorities): Supervisor
    {
        return new Supervisor(SupervisorId::generate(), 'Test Supervisor', $authorities);
    }

    private function createActiveContract(ClientId $clientId, SupervisorId $supervisorId): Contract
    {
        return new Contract(ContractId::generate(), $clientId, $supervisorId, true);
    }
}
