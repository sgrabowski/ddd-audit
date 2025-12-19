<?php

declare(strict_types=1);

namespace Audit\Domain\Entity;

use Audit\Domain\Exception\AlreadySuspendedException;
use Audit\Domain\Exception\AlreadyWithdrawnException;
use Audit\Domain\Exception\CannotLockExpiredException;
use Audit\Domain\Exception\CannotSuspendWithdrawnException;
use Audit\Domain\Exception\CannotUnlockException;
use Audit\Domain\Exception\ManagerCannotBeWatcherException;
use Audit\Domain\Exception\OwnerCannotBeWatcherException;
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
     * @var array<int, ClientId|SupervisorId>
     */
    private array $watchers = [];

    private function __construct(
        private readonly EvaluationId $id,
        private readonly ClientId $ownerId,
        private SupervisorId $managerId,
        private readonly StandardId $standardId,
        private readonly EvaluationReport $report,
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

    public function suspend(DateTimeImmutable $at, Clock $clock): void
    {
        if ($this->isExpired($clock)) {
            throw CannotLockExpiredException::create();
        }

        if ($this->isSuspended()) {
            throw AlreadySuspendedException::create();
        }

        if ($this->isWithdrawn()) {
            throw CannotSuspendWithdrawnException::create();
        }

        $this->suspension = new Suspension($at);
    }

    public function unlock(): void
    {
        if (!$this->isSuspended()) {
            throw CannotUnlockException::notSuspended();
        }

        $this->suspension = null;
    }

    public function withdraw(DateTimeImmutable $at, Clock $clock): void
    {
        if ($this->isExpired($clock)) {
            throw CannotLockExpiredException::create();
        }

        if ($this->isWithdrawn()) {
            throw AlreadyWithdrawnException::create();
        }

        $this->withdrawal = new Withdrawal($at);
    }

    public function changeManager(SupervisorId $newManagerId): void
    {
        $this->managerId = $newManagerId;
    }

    public function addWatcher(ClientId|SupervisorId $watcherId): void
    {
        if ($watcherId instanceof ClientId && $watcherId->equals($this->ownerId)) {
            throw OwnerCannotBeWatcherException::create();
        }

        if ($watcherId instanceof SupervisorId && $watcherId->equals($this->managerId)) {
            throw ManagerCannotBeWatcherException::create();
        }

        foreach ($this->watchers as $existing) {
            if ($this->watcherIdsEqual($existing, $watcherId)) {
                return;
            }
        }

        $this->watchers[] = $watcherId;
    }

    public function removeWatcher(ClientId|SupervisorId $watcherId): void
    {
        $this->watchers = array_filter(
            $this->watchers,
            fn ($watcher) => !$this->watcherIdsEqual($watcher, $watcherId)
        );
    }

    private function watcherIdsEqual(ClientId|SupervisorId $a, ClientId|SupervisorId $b): bool
    {
        return $a->toString() === $b->toString();
    }

    /**
     * @return array<int, ClientId|SupervisorId>
     */
    public function getWatchers(): array
    {
        return $this->watchers;
    }
}
