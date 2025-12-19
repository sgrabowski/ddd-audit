<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\Entity;

use Audit\Domain\Entity\Evaluation;
use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\EvaluationId;
use Audit\Domain\ValueObject\EvaluationReport;
use Audit\Domain\ValueObject\Rating;
use Audit\Domain\ValueObject\StandardId;
use Audit\Domain\ValueObject\SupervisorId;
use Audit\Tests\Unit\Support\FixedClock;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EvaluationTest extends TestCase
{
    public function test_can_record_evaluation(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $id = EvaluationId::generate();
        $ownerId = ClientId::generate();
        $managerId = SupervisorId::generate();
        $standardId = StandardId::generate();
        $report = EvaluationReport::create(
            Rating::Positive,
            new DateTimeImmutable('2024-01-15'),
            new DateTimeImmutable('2024-07-15'),
            $standardId,
            $clock
        );

        $evaluation = Evaluation::record($id, $ownerId, $managerId, $standardId, $report);

        $this->assertTrue($evaluation->getId()->equals($id));
        $this->assertTrue($evaluation->getOwnerId()->equals($ownerId));
        $this->assertTrue($evaluation->getManagerId()->equals($managerId));
        $this->assertTrue($evaluation->getStandardId()->equals($standardId));
        $this->assertSame($report, $evaluation->getReport());
    }

    public function test_is_not_expired_when_before_expiration_date(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $auditDate = new DateTimeImmutable('2024-01-15');
        $expirationDate = new DateTimeImmutable('2024-08-01'); // Still valid

        $evaluation = $this->createEvaluationWithDates($auditDate, $expirationDate, $clock);

        $this->assertFalse($evaluation->isExpired($clock));
    }

    public function test_is_expired_when_after_expiration_date(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $auditDate = new DateTimeImmutable('2020-01-15');
        $expirationDate = new DateTimeImmutable('2020-07-15'); // Expired

        $evaluation = $this->createEvaluationWithDates($auditDate, $expirationDate, $clock);

        $this->assertTrue($evaluation->isExpired($clock));
    }

    public function test_is_active_when_not_expired_and_not_replaced(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $auditDate = new DateTimeImmutable('2024-01-15');
        $expirationDate = new DateTimeImmutable('2024-08-01'); // Still valid

        $evaluation = $this->createEvaluationWithDates($auditDate, $expirationDate, $clock);

        $this->assertTrue($evaluation->isActive($clock));
    }

    public function test_is_not_suspended_initially(): void
    {
        $evaluation = $this->createEvaluation();

        $this->assertFalse($evaluation->isSuspended());
    }

    public function test_is_not_withdrawn_initially(): void
    {
        $evaluation = $this->createEvaluation();

        $this->assertFalse($evaluation->isWithdrawn());
    }

    public function test_is_not_locked_initially(): void
    {
        $evaluation = $this->createEvaluation();

        $this->assertFalse($evaluation->isLocked());
    }

    public function test_is_not_replaced_initially(): void
    {
        $evaluation = $this->createEvaluation();

        $this->assertFalse($evaluation->isReplaced());
    }

    private function createEvaluation(
        ?string $auditDate = null,
        ?string $expirationDate = null,
    ): Evaluation {
        $clock = FixedClock::at('2024-06-01');
        $auditDate ??= '2024-01-15';
        $expirationDate ??= '2024-07-15';

        return $this->createEvaluationWithDates(
            new DateTimeImmutable($auditDate),
            new DateTimeImmutable($expirationDate),
            $clock
        );
    }

    private function createEvaluationWithDates(
        DateTimeImmutable $auditDate,
        DateTimeImmutable $expirationDate,
        FixedClock $clock
    ): Evaluation {
        $standardId = StandardId::generate();

        $report = EvaluationReport::create(
            Rating::Positive,
            $auditDate,
            $expirationDate,
            $standardId,
            $clock
        );

        return Evaluation::record(
            EvaluationId::generate(),
            ClientId::generate(),
            SupervisorId::generate(),
            $standardId,
            $report
        );
    }

    public function test_is_active_on_specific_date_when_not_expired(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $evaluation = $this->createEvaluationWithDates(
            new DateTimeImmutable('2024-01-15'),
            new DateTimeImmutable('2024-08-15'),
            $clock
        );

        $checkDate = new DateTimeImmutable('2024-07-01');
        $this->assertTrue($evaluation->isActiveOn($checkDate));
    }

    public function test_is_not_active_on_date_after_expiration(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $evaluation = $this->createEvaluationWithDates(
            new DateTimeImmutable('2024-01-15'),
            new DateTimeImmutable('2024-07-15'),
            $clock
        );

        $checkDate = new DateTimeImmutable('2024-08-01'); // After expiration
        $this->assertFalse($evaluation->isActiveOn($checkDate));
    }

    public function test_can_be_marked_as_replaced(): void
    {
        $evaluation = $this->createEvaluation();
        $newEvaluationId = EvaluationId::generate();

        $this->assertFalse($evaluation->isReplaced());

        $evaluation->markAsReplaced($newEvaluationId);

        $this->assertTrue($evaluation->isReplaced());
    }

    public function test_is_not_active_when_replaced(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $evaluation = $this->createEvaluationWithDates(
            new DateTimeImmutable('2024-01-15'),
            new DateTimeImmutable('2025-01-15'), // Not expired
            $clock
        );

        $evaluation->markAsReplaced(EvaluationId::generate());

        $this->assertFalse($evaluation->isActive($clock));
    }
}
