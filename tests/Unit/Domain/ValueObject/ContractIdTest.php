<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\ValueObject;

use Audit\Domain\ValueObject\ContractId;
use PHPUnit\Framework\TestCase;

final class ContractIdTest extends TestCase
{
    public function test_can_be_created_and_compared(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $id1 = ContractId::fromString($uuid);
        $id2 = ContractId::fromString($uuid);

        $this->assertSame($uuid, $id1->toString());
        $this->assertTrue($id1->equals($id2));
    }
}
