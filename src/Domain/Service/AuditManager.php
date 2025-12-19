<?php

declare(strict_types=1);

namespace Audit\Domain\Service;

use Audit\Domain\Exception\NoActiveContractException;
use Audit\Domain\Repository\ContractRepository;
use Audit\Domain\Repository\QualityAuditRepository;
use Audit\Domain\ValueObject\ClientId;
use Audit\Domain\ValueObject\StandardId;
use Audit\Domain\ValueObject\SupervisorId;

final readonly class AuditManager
{
    public function __construct(
        private ContractRepository $contractRepository,
        private QualityAuditRepository $qualityAuditRepository,
    ) {
    }

    public function changeManager(
        ClientId $clientId,
        StandardId $standardId,
        SupervisorId $newManagerId
    ): void {
        if (!$this->contractRepository->hasActiveContract($clientId, $newManagerId)) {
            throw NoActiveContractException::between(
                $clientId->toString(),
                $newManagerId->toString()
            );
        }

        $audit = $this->qualityAuditRepository->findFor($clientId, $standardId);
        if ($audit === null) {
            throw new \DomainException('No audit found for client and standard');
        }

        $current = $this->getCurrentEvaluation($audit);
        $current->changeManager($newManagerId);

        $this->qualityAuditRepository->save($audit);
    }

    public function addWatcher(
        ClientId $clientId,
        StandardId $standardId,
        ClientId|SupervisorId $watcherId
    ): void {
        $audit = $this->qualityAuditRepository->findFor($clientId, $standardId);
        if ($audit === null) {
            throw new \DomainException('No audit found for client and standard');
        }

        $current = $this->getCurrentEvaluation($audit);
        $current->addWatcher($watcherId);

        $this->qualityAuditRepository->save($audit);
    }

    public function removeWatcher(
        ClientId $clientId,
        StandardId $standardId,
        ClientId|SupervisorId $watcherId
    ): void {
        $audit = $this->qualityAuditRepository->findFor($clientId, $standardId);
        if ($audit === null) {
            throw new \DomainException('No audit found for client and standard');
        }

        $current = $this->getCurrentEvaluation($audit);
        $current->removeWatcher($watcherId);

        $this->qualityAuditRepository->save($audit);
    }

    private function getCurrentEvaluation(\Audit\Domain\Entity\QualityAudit $audit): \Audit\Domain\Entity\Evaluation
    {
        $evaluations = $audit->getEvaluations();
        if (empty($evaluations)) {
            throw new \DomainException('No evaluations exist');
        }

        return end($evaluations);
    }
}
