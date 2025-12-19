<?php

declare(strict_types=1);

namespace Audit\Domain\Service;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
