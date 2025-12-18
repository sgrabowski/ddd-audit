<?php

declare(strict_types=1);

namespace Audit\Domain\Entity;

use Audit\Domain\Exception\CannotAuditTooSoonException;
use Audit\Domain\Service\Clock;
use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\EvaluationId;
use Audit\Domain\ValueObject\EvaluationReport;
use Audit\Domain\ValueObject\Rating;
use Audit\Domain\ValueObject\StandardId;
use Audit\Domain\ValueObject\SupervisorId;
use DateTimeImmutable;

final class QualityAudit
{
    private const int POSITIVE_EVALUATION_DELAY_DAYS = 180;
    private const int NEGATIVE_EVALUATION_DELAY_DAYS = 30;

    /**
     * @var array<Evaluation>
     */
    private array $evaluations = [];

    public function __construct(
        private readonly ClientId $clientId,
        private readonly StandardId $standardId,
    ) {
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getStandardId(): StandardId
    {
        return $this->standardId;
    }

    public function recordEvaluation(
        SupervisorId $supervisorId,
        Rating $rating,
        DateTimeImmutable $auditDate,
        DateTimeImmutable $expirationDate,
        Clock $clock
    ): Evaluation {
        $this->validateTiming($auditDate);

        $evaluationId = EvaluationId::generate();
        $report = EvaluationReport::create($rating, $auditDate, $expirationDate, $this->standardId, $clock);

        $evaluation = Evaluation::record(
            $evaluationId,
            $this->clientId,
            $supervisorId,
            $this->standardId,
            $report
        );

        if ($rating->isPositive()) {
            $this->replaceActivePositive($auditDate, $evaluationId);
        }

        $this->evaluations[] = $evaluation;

        return $evaluation;
    }

    /**
     * @return array<Evaluation>
     */
    public function getEvaluations(): array
    {
        return $this->evaluations;
    }

    private function validateTiming(DateTimeImmutable $newAuditDate): void
    {
        $mostRecent = $this->findMostRecent();

        if ($mostRecent === null) {
            return;
        }

        if ($mostRecent->getReport()->rating->isPositive()) {
            $this->validateTimingAfterPositiveEvaluation($mostRecent, $newAuditDate);
        } else {
            $this->validateTimingAfterNegativeEvaluation($mostRecent, $newAuditDate);
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

    private function replaceActivePositive(DateTimeImmutable $newAuditDate, EvaluationId $newId): void
    {
        $activePositive = $this->findActivePositiveOn($newAuditDate);

        if ($activePositive !== null) {
            $activePositive->markAsReplaced($newId);
        }
    }

    private function findMostRecent(): ?Evaluation
    {
        if (empty($this->evaluations)) {
            return null;
        }

        $sorted = $this->evaluations;
        usort(
            $sorted,
            fn(Evaluation $a, Evaluation $b) =>
                $b->getReport()->auditDate <=> $a->getReport()->auditDate
        );

        return $sorted[0];
    }

    private function findActivePositiveOn(DateTimeImmutable $date): ?Evaluation
    {
        foreach ($this->evaluations as $eval) {
            if ($eval->getReport()->rating->isPositive() && $eval->isActiveOn($date)) {
                return $eval;
            }
        }

        return null;
    }
}
