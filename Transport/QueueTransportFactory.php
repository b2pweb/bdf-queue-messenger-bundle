<?php

namespace Bdf\QueueMessengerBundle\Transport;

use Bdf\Dsn\Dsn;
use Bdf\Queue\Destination\DestinationManager;
use Bdf\QueueMessengerBundle\Transport\Stamp\PhpStampsSerializer;
use Bdf\QueueMessengerBundle\Transport\Stamp\StampsSerializerInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 *
 */
class QueueTransportFactory implements TransportFactoryInterface
{
    private $manager;
    private $stampsSerializer;

    /**
     * QueueTransportFactory constructor.
     *
     * @param DestinationManager $manager
     * @param StampsSerializerInterface|null $stampsSerializer
     */
    public function __construct(DestinationManager $manager, StampsSerializerInterface $stampsSerializer = null)
    {
        $this->manager = $manager;
        $this->stampsSerializer = $stampsSerializer ?: new PhpStampsSerializer();
    }

    /**
     * @param string $dsn
     * @param array $options
     * @param SerializerInterface $serializer
     *
     * @return TransportInterface
     */
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $request = Dsn::parse($dsn);

        return new QueueTransport($this->manager->guess($request->getHost()), $this->stampsSerializer, $request->query('consumer_timeout', 1));
    }

    /**
     * @param string $dsn
     * @param array $options
     *
     * @return bool
     */
    public function supports(string $dsn, array $options): bool
    {
        return 0 === strpos($dsn, 'bdfqueue');
    }
}
