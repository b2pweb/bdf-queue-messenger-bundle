<?php

namespace Bdf\QueueMessengerBundle\Tests\Transport;

use Bdf\Queue\Connection\Factory\ConnectionDriverFactoryInterface;
use Bdf\Queue\Destination\DestinationFactoryInterface;
use Bdf\Queue\Destination\DestinationManager;
use Bdf\QueueMessengerBundle\Transport\QueueTransport;
use Bdf\QueueMessengerBundle\Transport\QueueTransportFactory;
use Bdf\QueueMessengerBundle\Transport\Stamp\NullStampsSerializer;
use Bdf\QueueMessengerBundle\Transport\Stamp\PhpStampsSerializer;
use Bdf\QueueMessengerBundle\Transport\Stamp\StampsSerializerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class QueueTransportFactoryTest extends TestCase
{
    public function testFactory()
    {
        $destinations = new DestinationManager(
            $this->createMock(ConnectionDriverFactoryInterface::class),
            $this->createMock(DestinationFactoryInterface::class)
        );

        $factory = new QueueTransportFactory($destinations);

        $this->assertTrue($factory->supports('bdfqueue://b2p_bus?consumer_timeout=1', []));
        $this->assertFalse($factory->supports('amqp://root@localhost', []));
    }

    public function testCreateTransport()
    {
        $destinations = new DestinationManager(
            $this->createMock(ConnectionDriverFactoryInterface::class),
            $this->createMock(DestinationFactoryInterface::class)
        );

        $factory = new QueueTransportFactory($destinations);
        $transport = $factory->createTransport('bdfqueue://b2p_bus?consumer_timeout=1', [], $this->createMock(SerializerInterface::class));

        $this->assertInstanceOf(QueueTransport::class, $transport);
    }

    public function testCreateTransportWithConfiguredStampSerializer()
    {
        $destinations = new DestinationManager(
            $this->createMock(ConnectionDriverFactoryInterface::class),
            $this->createMock(DestinationFactoryInterface::class)
        );

        $factory = new QueueTransportFactory($destinations);
        $this->assertEquals(new QueueTransport($destinations->create('b2p_bus'), new NullStampsSerializer()), $factory->createTransport('bdfqueue://b2p_bus?stamp_serializer=null', [], $this->createMock(SerializerInterface::class)));
        $this->assertEquals(new QueueTransport($destinations->create('b2p_bus'), new PhpStampsSerializer()), $factory->createTransport('bdfqueue://b2p_bus?stamp_serializer=php', [], $this->createMock(SerializerInterface::class)));

        $customSerializer = $this->createMock(StampsSerializerInterface::class);
        $factory->registerStampSerializer('custom', function () use ($customSerializer) { return $customSerializer; });

        $this->assertEquals(new QueueTransport($destinations->create('b2p_bus'), $customSerializer), $factory->createTransport('bdfqueue://b2p_bus?stamp_serializer=custom', [], $this->createMock(SerializerInterface::class)));
    }
}
