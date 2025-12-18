<?php

declare(strict_types=1);

namespace Audit\Infrastructure\Repository;

use Audit\Domain\Entity\Evaluation;
use Audit\Domain\Repository\EvaluationRepository;
use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\EvaluationId;
use Audit\Domain\ValueObject\StandardId;

final class InMemoryEvaluationRepository implements EvaluationRepository
{
    /**
     * @param array<Evaluation> $evaluations
     */
    public function __construct(
        private array $evaluations = []
    ) {
    }

    public function save(Evaluation $evaluation): void
    {
        $this->evaluations[$evaluation->getId()->toString()] = $evaluation;
    }

    public function findById(EvaluationId $id): ?Evaluation
    {
        return $this->evaluations[$id->toString()] ?? null;
    }

    public function findMostRecentFor(ClientId $clientId, StandardId $standardId): ?Evaluation
    {
        $matching = array_filter(
            $this->evaluations,
            fn(Evaluation $e) =>
                $e->getOwnerId()->equals($clientId)
                && $e->getStandardId()->equals($standardId)
        );

        if (empty($matching)) {
            return null;
        }

        usort(
            $matching,
            fn(Evaluation $a, Evaluation $b) =>
                $b->getReport()->auditDate <=> $a->getReport()->auditDate
        );

        return $matching[0];
    }
}
