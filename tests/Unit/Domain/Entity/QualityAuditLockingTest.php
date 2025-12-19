<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\Entity;

use Audit\Domain\Entity\QualityAudit;
use Audit\Domain\Event\EvaluationSuspended;
use Audit\Domain\Event\EvaluationUnlocked;
use Audit\Domain\Event\EvaluationWithdrawn;
use Audit\Domain\Exception\AlreadySuspendedException;
use Audit\Domain\Exception\AlreadyWithdrawnException;
use Audit\Domain\Exception\CannotLockExpiredException;
use Audit\Domain\Exception\CannotSuspendWithdrawnException;
use Audit\Domain\Exception\CannotUnlockException;
use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\Rating;
use Audit\Domain\ValueObject\StandardId;
use Audit\Domain\ValueObject\SupervisorId;
use Audit\Tests\Unit\Support\FixedClock;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class QualityAuditLockingTest extends TestCase
{
    public function test_can_suspend_active_evaluation(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $audit = $this->createAuditWithEvaluation($clock);

        $audit->suspendCurrent($clock);
        $events = $audit->popEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(EvaluationSuspended::class, $events[0]);
    }

    public function test_can_unlock_suspended_evaluation(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $audit = $this->createAuditWithEvaluation($clock);

        $audit->suspendCurrent($clock);
        $audit->popEvents(); // Clear suspend event

        $audit->unlockCurrent($clock);
        $events = $audit->popEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(EvaluationUnlocked::class, $events[0]);
    }

    public function test_can_withdraw_from_normal_state(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $audit = $this->createAuditWithEvaluation($clock);

        $audit->withdrawCurrent($clock);
        $events = $audit->popEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(EvaluationWithdrawn::class, $events[0]);
    }

    public function test_can_withdraw_from_suspended_state(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $audit = $this->createAuditWithEvaluation($clock);

        $audit->suspendCurrent($clock);
        $audit->popEvents();

        $audit->withdrawCurrent($clock);
        $events = $audit->popEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(EvaluationWithdrawn::class, $events[0]);
    }

    public function test_cannot_suspend_already_suspended(): void
    {
        $this->expectException(AlreadySuspendedException::class);

        $clock = FixedClock::at('2024-06-01');
        $audit = $this->createAuditWithEvaluation($clock);

        $audit->suspendCurrent($clock);
        $audit->suspendCurrent($clock); // Should fail
    }

    public function test_cannot_suspend_withdrawn(): void
    {
        $this->expectException(CannotSuspendWithdrawnException::class);

        $clock = FixedClock::at('2024-06-01');
        $audit = $this->createAuditWithEvaluation($clock);

        $audit->withdrawCurrent($clock);
        $audit->suspendCurrent($clock); // Should fail
    }

    public function test_cannot_unlock_when_not_suspended(): void
    {
        $this->expectException(CannotUnlockException::class);

        $clock = FixedClock::at('2024-06-01');
        $audit = $this->createAuditWithEvaluation($clock);

        $audit->unlockCurrent($clock); // Not suspended - should fail
    }

    public function test_cannot_withdraw_already_withdrawn(): void
    {
        $this->expectException(AlreadyWithdrawnException::class);

        $clock = FixedClock::at('2024-06-01');
        $audit = $this->createAuditWithEvaluation($clock);

        $audit->withdrawCurrent($clock);
        $audit->withdrawCurrent($clock); // Should fail
    }

    public function test_cannot_lock_expired_evaluation(): void
    {
        $this->expectException(CannotLockExpiredException::class);

        $clock = FixedClock::at('2024-06-01');
        $audit = new QualityAudit(ClientId::generate(), StandardId::generate());

        // Create evaluation that's already expired
        $audit->recordEvaluation(
            SupervisorId::generate(),
            Rating::Positive,
            new DateTimeImmutable('2020-01-01'),
            new DateTimeImmutable('2020-07-01'), // Expired long ago
            $clock
        );

        $audit->suspendCurrent($clock); // Should fail - it's expired
    }

    private function createAuditWithEvaluation(FixedClock $clock): QualityAudit
    {
        $audit = new QualityAudit(ClientId::generate(), StandardId::generate());

        $audit->recordEvaluation(
            SupervisorId::generate(),
            Rating::Positive,
            new DateTimeImmutable('2024-05-01'),
            new DateTimeImmutable('2025-05-01'), // Not expired
            $clock
        );

        return $audit;
    }
}
