<?php

declare(strict_types=1);

namespace Audit\Application\Repository\Projection;

use Audit\Application\Projection\LockHistoryProjection;

interface LockHistoryProjectionRepository
{
    public function save(LockHistoryProjection $projection): void;

    /**
     * @return array<LockHistoryProjection>
     */
    public function findByEvaluationId(string $evaluationId): array;
}
