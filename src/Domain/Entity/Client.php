<?php

declare(strict_types=1);

namespace Audit\Domain\Entity;

use Audit\Domain\ValueObject\ClientId;

final class Client
{
    public function __construct(
        private readonly ClientId $id,
        private readonly string $name,
    ) {
    }

    public function getId(): ClientId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
