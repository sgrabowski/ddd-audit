<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\ValueObject;

use Audit\Domain\ValueObject\ClientId;
use PHPUnit\Framework\TestCase;

final class ClientIdTest extends TestCase
{
    public function test_can_be_created_from_string(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $id = ClientId::fromString($uuid);

        $this->assertSame($uuid, $id->toString());
    }

    public function test_can_generate_new_id(): void
    {
        $id = ClientId::generate();

        $this->assertNotEmpty($id->toString());
    }

    public function test_equals_works_correctly(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $id1 = ClientId::fromString($uuid);
        $id2 = ClientId::fromString($uuid);

        $this->assertTrue($id1->equals($id2));
    }
}
