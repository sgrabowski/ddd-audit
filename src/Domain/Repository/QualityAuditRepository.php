<?php

declare(strict_types=1);

namespace Audit\Domain\Repository;

use Audit\Domain\Entity\QualityAudit;
use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\StandardId;

interface QualityAuditRepository
{
    public function save(QualityAudit $audit): void;

    public function findFor(ClientId $clientId, StandardId $standardId): ?QualityAudit;
}
