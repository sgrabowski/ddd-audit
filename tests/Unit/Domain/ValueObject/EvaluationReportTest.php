<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\ValueObject;

use Audit\Domain\Exception\AuditDateCannotBeInFutureException;
use Audit\Domain\Exception\ExpirationDateTooEarlyException;
use Audit\Domain\ValueObject\EvaluationReport;
use Audit\Domain\ValueObject\Rating;
use Audit\Domain\ValueObject\StandardId;
use Audit\Tests\Unit\Support\FixedClock;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EvaluationReportTest extends TestCase
{
    public function test_can_create_valid_report(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $auditDate = new DateTimeImmutable('2024-01-15');
        $expirationDate = new DateTimeImmutable('2024-07-15');
        $standardId = StandardId::generate();

        $report = EvaluationReport::create(
            Rating::Positive,
            $auditDate,
            $expirationDate,
            $standardId,
            $clock
        );

        $this->assertSame(Rating::Positive, $report->rating);
        $this->assertSame($auditDate, $report->auditDate);
        $this->assertSame($expirationDate, $report->expirationDate);
        $this->assertTrue($report->standardId->equals($standardId));
    }

    public function test_rejects_expiration_less_than_180_days(): void
    {
        $this->expectException(ExpirationDateTooEarlyException::class);

        $clock = FixedClock::at('2024-06-01');
        $auditDate = new DateTimeImmutable('2024-01-15');
        $expirationDate = new DateTimeImmutable('2024-07-12');

        EvaluationReport::create(
            Rating::Positive,
            $auditDate,
            $expirationDate,
            StandardId::generate(),
            $clock
        );
    }

    public function test_allows_expiration_exactly_180_days(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $auditDate = new DateTimeImmutable('2024-01-15');
        $expirationDate = $auditDate->modify('+180 days');

        $report = EvaluationReport::create(
            Rating::Positive,
            $auditDate,
            $expirationDate,
            StandardId::generate(),
            $clock
        );

        $this->assertSame($expirationDate, $report->expirationDate);
    }

    public function test_rejects_audit_date_in_future(): void
    {
        $this->expectException(AuditDateCannotBeInFutureException::class);

        $clock = FixedClock::at('2024-06-01');
        $futureDate = new DateTimeImmutable('2024-06-02');
        $expirationDate = $futureDate->modify('+180 days');

        EvaluationReport::create(
            Rating::Negative,
            $futureDate,
            $expirationDate,
            StandardId::generate(),
            $clock
        );
    }

    public function test_allows_historical_audit_dates(): void
    {
        $clock = FixedClock::at('2024-06-01');
        $pastAuditDate = new DateTimeImmutable('2020-01-01');
        $pastExpirationDate = new DateTimeImmutable('2020-07-01');

        $report = EvaluationReport::create(
            Rating::Positive,
            $pastAuditDate,
            $pastExpirationDate,
            StandardId::generate(),
            $clock
        );

        $this->assertSame($pastAuditDate, $report->auditDate);
    }
}
