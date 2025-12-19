<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\Entity;

use Audit\Domain\Entity\QualityAudit;
use Audit\Domain\Exception\CannotAuditTooSoonException;
use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\Rating;
use Audit\Domain\ValueObject\StandardId;
use Audit\Domain\ValueObject\SupervisorId;
use Audit\Tests\Unit\Support\FixedClock;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class QualityAuditTest extends TestCase
{
    public function test_can_record_first_evaluation(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $clientId = ClientId::generate();
        $standardId = StandardId::generate();
        $audit = new QualityAudit($clientId, $standardId);

        $evaluation = $audit->recordEvaluation(
            SupervisorId::generate(),
            Rating::Positive,
            new DateTimeImmutable('2024-05-01'),
            new DateTimeImmutable('2024-11-01'),
            $clock
        );

        $this->assertTrue($evaluation->getOwnerId()->equals($clientId));
        $this->assertTrue($evaluation->getStandardId()->equals($standardId));
        $this->assertCount(1, $audit->getEvaluations());
    }

    public function test_rejects_second_positive_within_180_days(): void
    {
        $this->expectException(CannotAuditTooSoonException::class);

        $clock = FixedClock::at('2024-12-01');
        $audit = new QualityAudit(ClientId::generate(), StandardId::generate());

        $audit->recordEvaluation(
            SupervisorId::generate(),
            Rating::Positive,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-07-01'),
            $clock
        );

       
        $audit->recordEvaluation(
            SupervisorId::generate(),
            Rating::Positive,
            new DateTimeImmutable('2024-04-10'),
            new DateTimeImmutable('2024-10-10'),
            $clock
        );
    }

    public function test_allows_second_positive_after_180_days(): void
    {
        $clock = FixedClock::at('2024-12-01');
        $audit = new QualityAudit(ClientId::generate(), StandardId::generate());

        $audit->recordEvaluation(
            SupervisorId::generate(),
            Rating::Positive,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-07-01'),
            $clock
        );

       
        $second = $audit->recordEvaluation(
            SupervisorId::generate(),
            Rating::Positive,
            new DateTimeImmutable('2024-07-19'),
            new DateTimeImmutable('2025-01-19'),
            $clock
        );

        $this->assertCount(2, $audit->getEvaluations());
    }

    public function test_positive_replaces_active_positive(): void
    {
        $clock = FixedClock::at('2024-12-01');
        $audit = new QualityAudit(ClientId::generate(), StandardId::generate());

        $first = $audit->recordEvaluation(
            SupervisorId::generate(),
            Rating::Positive,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-08-01'),
            $clock
        );

       
        $second = $audit->recordEvaluation(
            SupervisorId::generate(),
            Rating::Positive,
            new DateTimeImmutable('2024-07-20'),
            new DateTimeImmutable('2025-01-20'),
            $clock
        );

        $this->assertTrue($first->isReplaced());
        $this->assertFalse($second->isReplaced());
    }

    public function test_negative_does_not_replace_prior(): void
    {
        $clock = FixedClock::at('2024-12-01');
        $audit = new QualityAudit(ClientId::generate(), StandardId::generate());

        $first = $audit->recordEvaluation(
            SupervisorId::generate(),
            Rating::Positive,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-08-01'),
            $clock
        );

       
        $second = $audit->recordEvaluation(
            SupervisorId::generate(),
            Rating::Negative,
            new DateTimeImmutable('2024-07-20'),
            new DateTimeImmutable('2025-01-20'),
            $clock
        );

        $this->assertFalse($first->isReplaced());
    }
}
