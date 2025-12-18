<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\Entity;

use Audit\Domain\Entity\Standard;
use Audit\Domain\ValueObject\StandardId;
use PHPUnit\Framework\TestCase;

final class StandardTest extends TestCase
{
    public function test_can_create_standard(): void
    {
        $id = StandardId::generate();
        $name = 'ISO 9001';

        $standard = new Standard($id, $name);

        $this->assertTrue($standard->getId()->equals($id));
        $this->assertSame($name, $standard->getName());
    }
}
