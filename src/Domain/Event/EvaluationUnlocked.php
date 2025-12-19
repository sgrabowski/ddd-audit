<?php

declare(strict_types=1);

namespace Audit\Domain\Event;

use Audit\Domain\ValueObject\EvaluationId;
use DateTimeImmutable;

final readonly class EvaluationUnlocked
{
    public function __construct(
        public EvaluationId $evaluationId,
        public DateTimeImmutable $unlockedAt,
    ) {
    }
}
