<?php

declare(strict_types=1);

namespace Audit\Infrastructure\Repository;

use Audit\Domain\Entity\QualityAudit;
use Audit\Domain\Repository\QualityAuditRepository;
use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\StandardId;

final class InMemoryQualityAuditRepository implements QualityAuditRepository
{
    /**
     * @var array<string, QualityAudit>
     */
    private array $audits = [];

    public function save(QualityAudit $audit): void
    {
        $key = $this->makeKey($audit);
        $this->audits[$key] = $audit;
    }

    public function findFor(ClientId $clientId, StandardId $standardId): ?QualityAudit
    {
        $key = $this->makeKeyFromIds($clientId, $standardId);
        return $this->audits[$key] ?? null;
    }

    private function makeKey(QualityAudit $audit): string
    {
        return $this->makeKeyFromIds($audit->getClientId(), $audit->getStandardId());
    }

    private function makeKeyFromIds(ClientId $clientId, StandardId $standardId): string
    {
        return $clientId->toString() . '::' . $standardId->toString();
    }
}
