<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\ValueObject;

use Audit\Domain\ValueObject\EvaluationId;
use PHPUnit\Framework\TestCase;

final class EvaluationIdTest extends TestCase
{
    public function test_can_be_created_from_string(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $id = EvaluationId::fromString($uuid);

        $this->assertSame($uuid, $id->toString());
    }

    public function test_can_generate_new_id(): void
    {
        $id = EvaluationId::generate();

        $this->assertNotEmpty($id->toString());
    }

    public function test_two_ids_with_same_value_are_equal(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $id1 = EvaluationId::fromString($uuid);
        $id2 = EvaluationId::fromString($uuid);

        $this->assertTrue($id1->equals($id2));
    }

    public function test_two_ids_with_different_values_are_not_equal(): void
    {
        $id1 = EvaluationId::generate();
        $id2 = EvaluationId::generate();

        $this->assertFalse($id1->equals($id2));
    }
}
