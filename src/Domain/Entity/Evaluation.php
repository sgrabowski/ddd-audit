<?php

declare(strict_types=1);

namespace Audit\Domain\Entity;

use Audit\Domain\Service\Clock;
use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\EvaluationId;
use Audit\Domain\ValueObject\EvaluationReport;
use Audit\Domain\ValueObject\StandardId;
use Audit\Domain\ValueObject\SupervisorId;
use Audit\Domain\ValueObject\Suspension;
use Audit\Domain\ValueObject\Withdrawal;
use DateTimeImmutable;

final class Evaluation
{
    private ?Suspension $suspension = null;
    private ?Withdrawal $withdrawal = null;
    private ?EvaluationId $replacedBy = null;

    /**
     * @param array<ClientId|SupervisorId> $watchers
     */
    private function __construct(
        private readonly EvaluationId $id,
        private readonly ClientId $ownerId,
        private SupervisorId $managerId,
        private readonly StandardId $standardId,
        private readonly EvaluationReport $report,
        private array $watchers = [],
    ) {
    }

    public static function record(
        EvaluationId $id,
        ClientId $ownerId,
        SupervisorId $managerId,
        StandardId $standardId,
        EvaluationReport $report
    ): self {
        return new self($id, $ownerId, $managerId, $standardId, $report);
    }

    public function getId(): EvaluationId
    {
        return $this->id;
    }

    public function getOwnerId(): ClientId
    {
        return $this->ownerId;
    }

    public function getManagerId(): SupervisorId
    {
        return $this->managerId;
    }

    public function getStandardId(): StandardId
    {
        return $this->standardId;
    }

    public function getReport(): EvaluationReport
    {
        return $this->report;
    }

    public function isExpired(Clock $clock): bool
    {
        return $this->isExpiredOn($clock->now());
    }

    public function isExpiredOn(DateTimeImmutable $date): bool
    {
        return $date > $this->report->expirationDate;
    }

    public function isSuspended(): bool
    {
        return $this->suspension !== null;
    }

    public function isWithdrawn(): bool
    {
        return $this->withdrawal !== null;
    }

    public function isLocked(): bool
    {
        return $this->isSuspended() || $this->isWithdrawn();
    }

    public function isReplaced(): bool
    {
        return $this->replacedBy !== null;
    }

    public function isActive(Clock $clock): bool
    {
        return $this->isActiveOn($clock->now());
    }

    public function isActiveOn(DateTimeImmutable $date): bool
    {
        return !$this->isExpiredOn($date)
            && !$this->isReplaced()
            && !$this->isLocked();
    }

    public function markAsReplaced(EvaluationId $replacedBy): void
    {
        $this->replacedBy = $replacedBy;
    }
}
