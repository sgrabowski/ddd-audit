<?php

declare(strict_types=1);

namespace Audit\Application\Projector;

use Audit\Application\Projection\LockHistoryProjection;
use Audit\Application\Repository\Projection\LockHistoryProjectionRepository;
use Audit\Domain\Event\EvaluationSuspended;
use Audit\Domain\Event\EvaluationUnlocked;
use Audit\Domain\Event\EvaluationWithdrawn;

final readonly class LockHistoryProjector
{
    public function __construct(
        private LockHistoryProjectionRepository $repository
    ) {
    }

    public function onEvaluationSuspended(EvaluationSuspended $event): void
    {
        $projection = new LockHistoryProjection(
            $event->evaluationId->toString(),
            'suspended',
            $event->suspendedAt
        );

        $this->repository->save($projection);
    }

    public function onEvaluationUnlocked(EvaluationUnlocked $event): void
    {
        $projection = new LockHistoryProjection(
            $event->evaluationId->toString(),
            'unlocked',
            $event->unlockedAt
        );

        $this->repository->save($projection);
    }

    public function onEvaluationWithdrawn(EvaluationWithdrawn $event): void
    {
        $projection = new LockHistoryProjection(
            $event->evaluationId->toString(),
            'withdrawn',
            $event->withdrawnAt
        );

        $this->repository->save($projection);
    }
}
