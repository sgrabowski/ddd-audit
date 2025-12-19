<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\Service;

use Audit\Domain\Entity\Contract;
use Audit\Domain\Entity\QualityAudit;
use Audit\Domain\Exception\NoActiveContractException;
use Audit\Domain\Exception\OwnerCannotBeWatcherException;
use Audit\Domain\Service\AuditManager;
use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\ContractId;
use Audit\Domain\ValueObject\Rating;
use Audit\Domain\ValueObject\StandardId;
use Audit\Domain\ValueObject\SupervisorId;
use Audit\Infrastructure\Repository\InMemoryContractRepository;
use Audit\Infrastructure\Repository\InMemoryQualityAuditRepository;
use Audit\Tests\Unit\Support\FixedClock;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AuditManagerTest extends TestCase
{
    public function test_can_change_manager_with_valid_contract(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $clientId = ClientId::generate();
        $standardId = StandardId::generate();
        $oldSupervisorId = SupervisorId::generate();
        $newSupervisorId = SupervisorId::generate();

        $audit = new QualityAudit($clientId, $standardId);
        $audit->recordEvaluation(
            $oldSupervisorId,
            Rating::Positive,
            new DateTimeImmutable('2024-05-01'),
            new DateTimeImmutable('2025-05-01'),
            $clock
        );

        $contract = new Contract(ContractId::generate(), $clientId, $newSupervisorId, true);
        $contracts = new InMemoryContractRepository([$contract]);
        $audits = new InMemoryQualityAuditRepository();
        $audits->save($audit);

        $manager = new AuditManager($contracts, $audits);
        $manager->changeManager($clientId, $standardId, $newSupervisorId);

        $reloaded = $audits->findFor($clientId, $standardId);
        $this->assertNotNull($reloaded);
        $evaluations = $reloaded->getEvaluations();
        $current = $evaluations[array_key_last($evaluations)];
        $this->assertTrue($current->getManagerId()->equals($newSupervisorId));
    }

    public function test_rejects_manager_change_without_contract(): void
    {
        $this->expectException(NoActiveContractException::class);

        $clock = FixedClock::at('2024-06-01');
        $clientId = ClientId::generate();
        $standardId = StandardId::generate();

        $audit = new QualityAudit($clientId, $standardId);
        $audit->recordEvaluation(
            SupervisorId::generate(),
            Rating::Positive,
            new DateTimeImmutable('2024-05-01'),
            new DateTimeImmutable('2025-05-01'),
            $clock
        );

        $contracts = new InMemoryContractRepository([]);
        $audits = new InMemoryQualityAuditRepository();
        $audits->save($audit);

        $manager = new AuditManager($contracts, $audits);
        $manager->changeManager($clientId, $standardId, SupervisorId::generate());
    }

    public function test_can_add_watcher(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $clientId = ClientId::generate();
        $standardId = StandardId::generate();
        $watcherId = ClientId::generate();

        $audit = new QualityAudit($clientId, $standardId);
        $audit->recordEvaluation(
            SupervisorId::generate(),
            Rating::Positive,
            new DateTimeImmutable('2024-05-01'),
            new DateTimeImmutable('2025-05-01'),
            $clock
        );

        $audits = new InMemoryQualityAuditRepository();
        $audits->save($audit);

        $manager = new AuditManager(new InMemoryContractRepository(), $audits);
        $manager->addWatcher($clientId, $standardId, $watcherId);

        $reloaded = $audits->findFor($clientId, $standardId);
        $this->assertNotNull($reloaded);
        $evaluations = $reloaded->getEvaluations();
        $current = $evaluations[array_key_last($evaluations)];
        $this->assertCount(1, $current->getWatchers());
    }

    public function test_rejects_owner_as_watcher(): void
    {
        $this->expectException(OwnerCannotBeWatcherException::class);

        $clock = FixedClock::at('2024-06-01');
        $clientId = ClientId::generate();
        $standardId = StandardId::generate();

        $audit = new QualityAudit($clientId, $standardId);
        $audit->recordEvaluation(
            SupervisorId::generate(),
            Rating::Positive,
            new DateTimeImmutable('2024-05-01'),
            new DateTimeImmutable('2025-05-01'),
            $clock
        );

        $audits = new InMemoryQualityAuditRepository();
        $audits->save($audit);

        $manager = new AuditManager(new InMemoryContractRepository(), $audits);
        $manager->addWatcher($clientId, $standardId, $clientId);
    }
}
