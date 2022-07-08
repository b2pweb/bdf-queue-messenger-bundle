<?php

namespace Bdf\QueueMessengerBundle\Tests\Transport\Stamp;

use Bdf\QueueMessengerBundle\Transport\Stamp\NullStampsSerializer;
use PHPUnit\Framework\TestCase;

class NullStampsSerializerTest extends TestCase
{
    public function testSerializer()
    {
        $serializer = new NullStampsSerializer();

        $this->assertNull($serializer->serialize([]));
        $this->assertSame([], $serializer->deserialize(''));
    }
}
