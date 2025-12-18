<?php

declare(strict_types=1);

namespace Audit\Domain\ValueObject;

use Audit\Domain\Exception\AuditDateCannotBeInFutureException;
use Audit\Domain\Exception\ExpirationDateTooEarlyException;
use Audit\Domain\Service\Clock;
use DateTimeImmutable;

final readonly class EvaluationReport
{
    private function __construct(
        public Rating $rating,
        public DateTimeImmutable $auditDate,
        public DateTimeImmutable $expirationDate,
        public StandardId $standardId,
    ) {
    }

    public static function create(
        Rating $rating,
        DateTimeImmutable $auditDate,
        DateTimeImmutable $expirationDate,
        StandardId $standardId,
        Clock $clock
    ): self {
        self::validate($auditDate, $expirationDate, $clock);

        return new self($rating, $auditDate, $expirationDate, $standardId);
    }

    private static function validate(
        DateTimeImmutable $auditDate,
        DateTimeImmutable $expirationDate,
        Clock $clock
    ): void {
        if ($auditDate > $clock->now()) {
            throw AuditDateCannotBeInFutureException::create();
        }

        $minimumExpirationDate = $auditDate->modify('+180 days');
        if ($expirationDate < $minimumExpirationDate) {
            throw ExpirationDateTooEarlyException::mustBeAtLeast180DaysFromAudit();
        }
    }
}

