<?php

declare(strict_types=1);

namespace Audit\Domain\Service;

use Audit\Domain\Entity\Client;
use Audit\Domain\Entity\Evaluation;
use Audit\Domain\Entity\Standard;
use Audit\Domain\Entity\Supervisor;
use Audit\Domain\Exception\NoActiveContractException;
use Audit\Domain\Exception\SupervisorNotAuthorizedException;
use Audit\Domain\Repository\ContractRepository;
use Audit\Domain\ValueObject\EvaluationId;
use Audit\Domain\ValueObject\EvaluationReport;
use Audit\Domain\ValueObject\Rating;
use DateTimeImmutable;

final class RecordEvaluationService
{
    public function __construct(
        private readonly ContractRepository $contractRepository,
        private readonly Clock $clock,
    ) {
    }

    public function recordEvaluation(
        Client $client,
        Supervisor $supervisor,
        Standard $standard,
        Rating $rating,
        DateTimeImmutable $auditDate,
        DateTimeImmutable $expirationDate
    ): Evaluation {
        $this->validatePrerequisites($client, $supervisor, $standard);

        $report = EvaluationReport::create(
            $rating,
            $auditDate,
            $expirationDate,
            $standard->getId(),
            $this->clock
        );

        return Evaluation::record(
            EvaluationId::generate(),
            $client->getId(),
            $supervisor->getId(),
            $standard->getId(),
            $report
        );
    }

    private function validatePrerequisites(
        Client $client,
        Supervisor $supervisor,
        Standard $standard
    ): void {
        if (!$this->contractRepository->hasActiveContract($client->getId(), $supervisor->getId())) {
            throw NoActiveContractException::between(
                $client->getId()->toString(),
                $supervisor->getId()->toString()
            );
        }

        if (!$supervisor->hasAuthorityFor($standard->getId())) {
            throw SupervisorNotAuthorizedException::forStandard(
                $supervisor->getId()->toString(),
                $standard->getName()
            );
        }
    }
}
