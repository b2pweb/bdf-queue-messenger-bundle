<?php

namespace Bdf\QueueMessengerBundle\Tests\Transport\Stamp;

use Bdf\QueueMessengerBundle\Transport\Stamp\PhpStampsSerializer;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class PhpStampsSerializerTest extends TestCase
{
    public function test_serializer()
    {
        $serializer = new PhpStampsSerializer();

        $this->assertSame('a:0:{}', $serializer->serialize([]));
        $this->assertSame([], $serializer->deserialize('a:0:{}'));
    }
}
