<?php

namespace Bdf\QueueMessengerBundle\Transport;

use Bdf\Dsn\Dsn;
use Bdf\Queue\Destination\DestinationManager;
use Bdf\QueueMessengerBundle\Transport\Stamp\NullStampsSerializer;
use Bdf\QueueMessengerBundle\Transport\Stamp\PhpStampsSerializer;
use Bdf\QueueMessengerBundle\Transport\Stamp\StampsSerializerInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class QueueTransportFactory implements TransportFactoryInterface
{
    private $manager;
    private $defaultStampsSerializer;

    /**
     * @var array<string, callable():StampsSerializerInterface>
     */
    private $serializersFactories = [];

    /**
     * QueueTransportFactory constructor.
     */
    public function __construct(DestinationManager $manager, StampsSerializerInterface $stampsSerializer = null)
    {
        $this->manager = $manager;
        $this->defaultStampsSerializer = $stampsSerializer ?: new PhpStampsSerializer();
        $this->serializersFactories = [
            'null' => function () { return new NullStampsSerializer(); },
            'php' => function () { return new PhpStampsSerializer(); },
        ];
    }

    /**
     * @param string                               $name    The serializer name
     * @param callable():StampsSerializerInterface $factory
     */
    public function registerStampSerializer(string $name, callable $factory): void
    {
        $this->serializersFactories[$name] = $factory;
    }

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $request = Dsn::parse($dsn);

        if ($stampSerializer = $request->query('stamp_serializer')) {
            $stampSerializer = ($this->serializersFactories[$stampSerializer])();
        } else {
            $stampSerializer = $this->defaultStampsSerializer;
        }

        return new QueueTransport($this->manager->guess($request->getHost()), $stampSerializer, $request->query('consumer_timeout', 1));
    }

    public function supports(string $dsn, array $options): bool
    {
        return 0 === strpos($dsn, 'bdfqueue');
    }
}
