<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\Entity;

use Audit\Domain\Entity\Supervisor;
use Audit\Domain\ValueObject\StandardId;
use Audit\Domain\ValueObject\SupervisorId;
use PHPUnit\Framework\TestCase;

final class SupervisorTest extends TestCase
{
    public function test_can_create_supervisor(): void
    {
        $id = SupervisorId::generate();
        $name = 'John Doe';

        $supervisor = new Supervisor($id, $name, []);

        $this->assertTrue($supervisor->getId()->equals($id));
        $this->assertSame($name, $supervisor->getName());
    }

    public function test_has_authority_when_standard_is_authorized(): void
    {
        $standardId = StandardId::generate();
        $supervisor = new Supervisor(
            SupervisorId::generate(),
            'Jane Smith',
            [$standardId]
        );

        $this->assertTrue($supervisor->hasAuthorityFor($standardId));
    }

    public function test_does_not_have_authority_when_standard_is_not_authorized(): void
    {
        $authorizedStandardId = StandardId::generate();
        $unauthorizedStandardId = StandardId::generate();

        $supervisor = new Supervisor(
            SupervisorId::generate(),
            'Bob Johnson',
            [$authorizedStandardId]
        );

        $this->assertFalse($supervisor->hasAuthorityFor($unauthorizedStandardId));
    }

    public function test_has_authority_for_multiple_standards(): void
    {
        $standard1 = StandardId::generate();
        $standard2 = StandardId::generate();

        $supervisor = new Supervisor(
            SupervisorId::generate(),
            'Alice Brown',
            [$standard1, $standard2]
        );

        $this->assertTrue($supervisor->hasAuthorityFor($standard1));
        $this->assertTrue($supervisor->hasAuthorityFor($standard2));
    }
}
