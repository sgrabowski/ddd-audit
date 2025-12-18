<?php

declare(strict_types=1);

namespace Audit\Domain\Entity;

use Audit\Domain\ValueObject\StandardId;

final class Standard
{
    public function __construct(
        private readonly StandardId $id,
        private readonly string $name,
    ) {
    }

    public function getId(): StandardId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
