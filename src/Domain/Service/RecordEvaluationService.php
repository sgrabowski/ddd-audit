<?php

declare(strict_types=1);

namespace Audit\Domain\Service;

use Audit\Domain\Entity\Client;
use Audit\Domain\Entity\Evaluation;
use Audit\Domain\Entity\Standard;
use Audit\Domain\Entity\Supervisor;
use Audit\Domain\Exception\CannotAuditTooSoonException;
use Audit\Domain\Exception\NoActiveContractException;
use Audit\Domain\Exception\SupervisorNotAuthorizedException;
use Audit\Domain\Repository\ContractRepository;
use Audit\Domain\Repository\EvaluationRepository;
use Audit\Domain\ValueObject\EvaluationId;
use Audit\Domain\ValueObject\EvaluationReport;
use Audit\Domain\ValueObject\Rating;
use DateTimeImmutable;

final class RecordEvaluationService
{
    private const int POSITIVE_EVALUATION_DELAY_DAYS = 180;
    private const int NEGATIVE_EVALUATION_DELAY_DAYS = 30;

    public function __construct(
        private readonly ContractRepository $contractRepository,
        private readonly Clock $clock,
        private readonly EvaluationRepository $evaluationRepository,
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
        $this->validateTiming($client, $standard, $auditDate);

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

    private function validateTiming(
        Client $client,
        Standard $standard,
        DateTimeImmutable $newAuditDate
    ): void {
        $priorEvaluation = $this->evaluationRepository->findMostRecentFor(
            $client->getId(),
            $standard->getId()
        );

        if ($priorEvaluation === null) {
            return;
        }

        if ($priorEvaluation->getReport()->rating->isPositive()) {
            $this->validateTimingAfterPositiveEvaluation($priorEvaluation, $newAuditDate);
        } else {
            $this->validateTimingAfterNegativeEvaluation($priorEvaluation, $newAuditDate);
        }
    }

    private function validateTimingAfterPositiveEvaluation(
        Evaluation $priorEvaluation,
        DateTimeImmutable $newAuditDate
    ): void {
        $daysPassed = $this->calculateDaysBetween(
            $priorEvaluation->getReport()->auditDate,
            $newAuditDate
        );

        if ($daysPassed < self::POSITIVE_EVALUATION_DELAY_DAYS) {
            throw CannotAuditTooSoonException::afterPositive(
                self::POSITIVE_EVALUATION_DELAY_DAYS,
                $daysPassed
            );
        }
    }

    private function validateTimingAfterNegativeEvaluation(
        Evaluation $priorEvaluation,
        DateTimeImmutable $newAuditDate
    ): void {
        $daysPassed = $this->calculateDaysBetween(
            $priorEvaluation->getReport()->auditDate,
            $newAuditDate
        );

        if ($daysPassed < self::NEGATIVE_EVALUATION_DELAY_DAYS) {
            throw CannotAuditTooSoonException::afterNegative(
                self::NEGATIVE_EVALUATION_DELAY_DAYS,
                $daysPassed
            );
        }
    }

    private function calculateDaysBetween(
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): int {
        return $from->diff($to)->days;
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
