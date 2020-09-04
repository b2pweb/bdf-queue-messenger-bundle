<?php

namespace Bdf\QueueMessengerBundle\Tests\Transport;

use Bdf\Queue\Connection\Factory\ConnectionDriverFactoryInterface;
use Bdf\Queue\Destination\DestinationFactoryInterface;
use Bdf\Queue\Destination\DestinationManager;
use Bdf\QueueMessengerBundle\Transport\QueueTransport;
use Bdf\QueueMessengerBundle\Transport\QueueTransportFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 *
 */
class QueueTransportFactoryTest extends TestCase
{
    public function test_factory()
    {
        $destinations = new DestinationManager(
            $this->createMock(ConnectionDriverFactoryInterface::class),
            $this->createMock(DestinationFactoryInterface::class)
        );

        $factory = new QueueTransportFactory($destinations);

        $this->assertTrue($factory->supports('bdfqueue://b2p_bus?consumer_timeout=1', []));
        $this->assertFalse($factory->supports('amqp://root@localhost', []));
    }

    public function test_create_transport()
    {
        $destinations = new DestinationManager(
            $this->createMock(ConnectionDriverFactoryInterface::class),
            $this->createMock(DestinationFactoryInterface::class)
        );

        $factory = new QueueTransportFactory($destinations);
        $transport = $factory->createTransport('bdfqueue://b2p_bus?consumer_timeout=1', [], $this->createMock(SerializerInterface::class));

        $this->assertInstanceOf(QueueTransport::class, $transport);
    }
}
