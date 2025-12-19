<?php

declare(strict_types=1);

namespace Audit\Application\Projection;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'lock_history_projection')]
class LockHistoryProjection
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 36)]
    private string $evaluationId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $action;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $occurredAt;

    public function __construct(
        string $evaluationId,
        string $action,
        DateTimeImmutable $occurredAt
    ) {
        $this->evaluationId = $evaluationId;
        $this->action = $action;
        $this->occurredAt = $occurredAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvaluationId(): string
    {
        return $this->evaluationId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
