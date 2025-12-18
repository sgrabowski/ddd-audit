<?php

declare(strict_types=1);

namespace Audit\Domain\Entity;

use Audit\Domain\ValueObject\StandardId;
use Audit\Domain\ValueObject\SupervisorId;

final class Supervisor
{
    /**
     * @param array<StandardId> $authorizedStandards
     */
    public function __construct(
        private readonly SupervisorId $id,
        private readonly string $name,
        private readonly array $authorizedStandards,
    ) {
    }

    public function getId(): SupervisorId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasAuthorityFor(StandardId $standardId): bool
    {
        foreach ($this->authorizedStandards as $authorized) {
            if ($authorized->equals($standardId)) {
                return true;
            }
        }

        return false;
    }
}
