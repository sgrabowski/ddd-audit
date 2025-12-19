<?php

declare(strict_types=1);

namespace Audit\Tests\Unit\Domain\Entity;

use Audit\Domain\Entity\Client;
use Audit\Domain\ValueObject\ClientId;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function test_can_create_client(): void
    {
        $id = ClientId::generate();
        $name = 'Acme Corporation';

        $client = new Client($id, $name);

        $this->assertTrue($client->getId()->equals($id));
        $this->assertSame($name, $client->getName());
    }
}
