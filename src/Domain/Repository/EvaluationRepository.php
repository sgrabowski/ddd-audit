<?php

declare(strict_types=1);

namespace Audit\Domain\Repository;

use Audit\Domain\Entity\Evaluation;
use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\EvaluationId;
use Audit\Domain\ValueObject\StandardId;

interface EvaluationRepository
{
    public function save(Evaluation $evaluation): void;

    public function findById(EvaluationId $id): ?Evaluation;

    public function findMostRecentFor(ClientId $clientId, StandardId $standardId): ?Evaluation;
}
