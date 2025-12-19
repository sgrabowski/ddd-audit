<?php

declare(strict_types=1);

namespace Audit\Infrastructure\Repository\Projection;

use Audit\Application\Projection\LockHistoryProjection;
use Audit\Application\Repository\Projection\LockHistoryProjectionRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineLockHistoryProjectionRepository implements LockHistoryProjectionRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function save(LockHistoryProjection $projection): void
    {
        $this->entityManager->persist($projection);
        $this->entityManager->flush();
    }

    public function findByEvaluationId(string $evaluationId): array
    {
        return $this->entityManager
            ->getRepository(LockHistoryProjection::class)
            ->findBy(['evaluationId' => $evaluationId], ['occurredAt' => 'ASC']);
    }
}
