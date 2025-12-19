<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\Service;

use Audit\Domain\Entity\Client;
use Audit\Domain\Entity\Contract;
use Audit\Domain\Entity\Standard;
use Audit\Domain\Entity\Supervisor;
use Audit\Domain\Exception\NoActiveContractException;
use Audit\Domain\Exception\SupervisorNotAuthorizedException;
use Audit\Domain\Service\AuditRecorder;
use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\ContractId;
use Audit\Domain\ValueObject\Rating;
use Audit\Domain\ValueObject\StandardId;
use Audit\Domain\ValueObject\SupervisorId;
use Audit\Infrastructure\Repository\InMemoryContractRepository;
use Audit\Infrastructure\Repository\InMemoryQualityAuditRepository;
use Audit\Tests\Unit\Support\FixedClock;
use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;

final class AuditRecorderTest extends TestCase
{
    public function test_records_evaluation_with_valid_contract_and_authority(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $client = $this->createClient();
        $standard = $this->createStandard();
        $supervisor = $this->createSupervisor([$standard->getId()]);
        $contract = $this->createActiveContract($client->getId(), $supervisor->getId());

        $contractRepo = new InMemoryContractRepository([$contract]);
        $auditRepo = new InMemoryQualityAuditRepository();
        $service = new AuditRecorder($contractRepo, $auditRepo, $clock);

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

        $contractRepo = new InMemoryContractRepository([]);
        $auditRepo = new InMemoryQualityAuditRepository();
        $service = new AuditRecorder($contractRepo, $auditRepo, $clock);

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
        $supervisor = $this->createSupervisor([$otherStandard->getId()]);
        $contract = $this->createActiveContract($client->getId(), $supervisor->getId());

        $contractRepo = new InMemoryContractRepository([$contract]);
        $auditRepo = new InMemoryQualityAuditRepository();
        $service = new AuditRecorder($contractRepo, $auditRepo, $clock);

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

    public function test_rejects_subsequent_positive_within_180_days(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('180 days');

        $clock = FixedClock::at('2024-06-01');
        $client = $this->createClient();
        $standard = $this->createStandard();
        $supervisor = $this->createSupervisor([$standard->getId()]);
        $contract = $this->createActiveContract($client->getId(), $supervisor->getId());

        $contractRepo = new InMemoryContractRepository([$contract]);
        $auditRepo = new InMemoryQualityAuditRepository();
        $service = new AuditRecorder($contractRepo, $auditRepo, $clock);


        $first = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-07-01')
        );


        $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            new DateTimeImmutable('2024-04-10'),
            new DateTimeImmutable('2024-10-10')
        );
    }

    public function test_allows_subsequent_positive_after_180_days(): void
    {
        $clock = FixedClock::at('2024-12-01');
        $client = $this->createClient();
        $standard = $this->createStandard();
        $supervisor = $this->createSupervisor([$standard->getId()]);
        $contract = $this->createActiveContract($client->getId(), $supervisor->getId());

        $contractRepo = new InMemoryContractRepository([$contract]);
        $auditRepo = new InMemoryQualityAuditRepository();
        $service = new AuditRecorder($contractRepo, $auditRepo, $clock);


        $first = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-07-01')
        );


        $second = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            new DateTimeImmutable('2024-07-19'),
            new DateTimeImmutable('2025-01-19')
        );

        $this->assertTrue($second->getOwnerId()->equals($client->getId()));
    }

    public function test_rejects_subsequent_negative_within_30_days(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('30 days');

        $clock = FixedClock::at('2024-06-01');
        $client = $this->createClient();
        $standard = $this->createStandard();
        $supervisor = $this->createSupervisor([$standard->getId()]);
        $contract = $this->createActiveContract($client->getId(), $supervisor->getId());

        $contractRepo = new InMemoryContractRepository([$contract]);
        $auditRepo = new InMemoryQualityAuditRepository();
        $service = new AuditRecorder($contractRepo, $auditRepo, $clock);


        $first = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Negative,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-07-01')
        );


        $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            new DateTimeImmutable('2024-01-21'),
            new DateTimeImmutable('2024-07-21')
        );
    }

    public function test_allows_subsequent_after_30_days_of_negative(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $client = $this->createClient();
        $standard = $this->createStandard();
        $supervisor = $this->createSupervisor([$standard->getId()]);
        $contract = $this->createActiveContract($client->getId(), $supervisor->getId());

        $contractRepo = new InMemoryContractRepository([$contract]);
        $auditRepo = new InMemoryQualityAuditRepository();
        $service = new AuditRecorder($contractRepo, $auditRepo, $clock);


        $first = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Negative,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-07-01')
        );


        $second = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            new DateTimeImmutable('2024-02-10'),
            new DateTimeImmutable('2024-08-10')
        );

        $this->assertTrue($second->getOwnerId()->equals($client->getId()));
    }

    public function test_allows_subsequent_exactly_180_days_after_positive(): void
    {
        $clock = FixedClock::at('2024-12-01');
        $client = $this->createClient();
        $standard = $this->createStandard();
        $supervisor = $this->createSupervisor([$standard->getId()]);
        $contract = $this->createActiveContract($client->getId(), $supervisor->getId());

        $contractRepo = new InMemoryContractRepository([$contract]);
        $auditRepo = new InMemoryQualityAuditRepository();
        $service = new AuditRecorder($contractRepo, $auditRepo, $clock);

        $firstAuditDate = new DateTimeImmutable('2024-01-01');
        $first = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            $firstAuditDate,
            new DateTimeImmutable('2024-07-01')
        );


        $secondAuditDate = $firstAuditDate->modify('+180 days');
        $second = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            $secondAuditDate,
            $secondAuditDate->modify('+180 days')
        );

        $this->assertTrue($second->getOwnerId()->equals($client->getId()));
    }

    public function test_allows_subsequent_exactly_30_days_after_negative(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $client = $this->createClient();
        $standard = $this->createStandard();
        $supervisor = $this->createSupervisor([$standard->getId()]);
        $contract = $this->createActiveContract($client->getId(), $supervisor->getId());

        $contractRepo = new InMemoryContractRepository([$contract]);
        $auditRepo = new InMemoryQualityAuditRepository();
        $service = new AuditRecorder($contractRepo, $auditRepo, $clock);

        $firstAuditDate = new DateTimeImmutable('2024-01-01');
        $first = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Negative,
            $firstAuditDate,
            new DateTimeImmutable('2024-07-01')
        );


        $secondAuditDate = $firstAuditDate->modify('+30 days');
        $second = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            $secondAuditDate,
            $secondAuditDate->modify('+180 days')
        );

        $this->assertTrue($second->getOwnerId()->equals($client->getId()));
    }

    public function test_positive_replaces_active_positive(): void
    {
        $clock = FixedClock::at('2024-12-01');
        $client = $this->createClient();
        $standard = $this->createStandard();
        $supervisor = $this->createSupervisor([$standard->getId()]);
        $contract = $this->createActiveContract($client->getId(), $supervisor->getId());

        $contractRepo = new InMemoryContractRepository([$contract]);
        $auditRepo = new InMemoryQualityAuditRepository();
        $service = new AuditRecorder($contractRepo, $auditRepo, $clock);


        $first = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-08-01')
        );


        $second = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            new DateTimeImmutable('2024-07-20'),
            new DateTimeImmutable('2025-01-20')
        );


        $this->assertTrue($first->isReplaced());
        $this->assertFalse($second->isReplaced());
    }

    public function test_negative_does_not_replace_prior_evaluation(): void
    {
        $clock = FixedClock::at('2024-12-01');
        $client = $this->createClient();
        $standard = $this->createStandard();
        $supervisor = $this->createSupervisor([$standard->getId()]);
        $contract = $this->createActiveContract($client->getId(), $supervisor->getId());

        $contractRepo = new InMemoryContractRepository([$contract]);
        $auditRepo = new InMemoryQualityAuditRepository();
        $service = new AuditRecorder($contractRepo, $auditRepo, $clock);


        $first = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-08-01')
        );


        $second = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Negative,
            new DateTimeImmutable('2024-07-20'),
            new DateTimeImmutable('2025-01-20')
        );

        $this->assertFalse($first->isReplaced());
    }

    public function test_no_replacement_when_prior_already_expired(): void
    {
        $clock = FixedClock::at('2024-12-01');
        $client = $this->createClient();
        $standard = $this->createStandard();
        $supervisor = $this->createSupervisor([$standard->getId()]);
        $contract = $this->createActiveContract($client->getId(), $supervisor->getId());

        $contractRepo = new InMemoryContractRepository([$contract]);
        $auditRepo = new InMemoryQualityAuditRepository();
        $service = new AuditRecorder($contractRepo, $auditRepo, $clock);


        $first = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-07-01')
        );


        $second = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            new DateTimeImmutable('2024-07-20'),
            new DateTimeImmutable('2025-01-20')
        );

        $this->assertFalse($first->isReplaced());
    }

    public function test_negative_cannot_be_replaced_by_subsequent_positive(): void
    {
        $clock = FixedClock::at('2024-12-01');
        $client = $this->createClient();
        $standard = $this->createStandard();
        $supervisor = $this->createSupervisor([$standard->getId()]);
        $contract = $this->createActiveContract($client->getId(), $supervisor->getId());

        $contractRepo = new InMemoryContractRepository([$contract]);
        $auditRepo = new InMemoryQualityAuditRepository();
        $service = new AuditRecorder($contractRepo, $auditRepo, $clock);


        $first = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Negative,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-07-01')
        );


        $second = $service->recordEvaluation(
            $client,
            $supervisor,
            $standard,
            Rating::Positive,
            new DateTimeImmutable('2024-02-10'),
            new DateTimeImmutable('2024-08-10')
        );


        $this->assertFalse($first->isReplaced());
    }
}
