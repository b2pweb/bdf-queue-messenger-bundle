<?php

namespace Bdf\QueueMessengerBundle\Transport;

use Bdf\Queue\Consumer\Receiver\StopWhenEmptyReceiver;
use Bdf\Queue\Consumer\Receiver\TimeLimiterReceiver;
use Bdf\Queue\Destination\DestinationInterface;
use Bdf\Queue\Message\EnvelopeInterface as QueuedEnvelope;
use Bdf\Queue\Message\Message as QueueMessage;
use Bdf\Queue\Testing\StackMessagesReceiver;
use Bdf\QueueMessengerBundle\Transport\Stamp\BdfQueueReceivedStamp;
use Bdf\QueueMessengerBundle\Transport\Stamp\DestinationStamp;
use Bdf\QueueMessengerBundle\Transport\Stamp\StampsSerializerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 *
 */
class QueueTransport implements TransportInterface
{
    /**
     * The destinations instance
     *
     * @var DestinationInterface
     */
    private $destination;

    /**
     * @var StampsSerializerInterface
     */
    private $stampsSerializer;

    /**
     * @var int
     */
    private $consumerTimeout;


    /**
     * Constructor
     *
     * @param DestinationInterface $manager
     * @param StampsSerializerInterface $stampsSerializer
     * @param int $consumerTimeout
     */
    public function __construct(DestinationInterface $destination, StampsSerializerInterface $stampsSerializer, int $consumerTimeout = 1)
    {
        $this->destination = $destination;
        $this->stampsSerializer = $stampsSerializer;
        $this->consumerTimeout = $consumerTimeout;
    }

    /**
     * {@inheritdoc}
     */
    public function get(): iterable
    {
        $stack = new StackMessagesReceiver();
        $extension = new StopWhenEmptyReceiver($stack);
        $extension = new TimeLimiterReceiver($extension, $this->consumerTimeout);

        $consumer = $this->destination->consumer($extension);
        $consumer->consume($this->consumerTimeout);

        foreach ($stack->messages() as $envelope) {
            yield $this
                ->toEnvelope($envelope->message()->data(), $envelope)
                ->with(new BdfQueueReceivedStamp($envelope))
            ;
        }
    }

    /**
     * Get the envelope from the message payload
     *
     * @param mixed $message
     * @param QueuedEnvelope $queuedEnvelope
     *
     * @return Envelope
     */
    private function toEnvelope($message, QueuedEnvelope $queuedEnvelope): Envelope
    {
        if ($message instanceof Envelope) {
            return $message;
        }

        return new Envelope($message, $this->unserializeStamps($queuedEnvelope));
    }

    /**
     * {@inheritdoc}
     */
    public function ack(Envelope $envelope): void
    {
        /** @var BdfQueueReceivedStamp $stamp */
        $stamp = $envelope->last(BdfQueueReceivedStamp::class);
        $stamp->getEnvelope()->acknowledge();
    }

    /**
     * {@inheritdoc}
     */
    public function reject(Envelope $envelope): void
    {
        /** @var BdfQueueReceivedStamp $stamp */
        $stamp = $envelope->last(BdfQueueReceivedStamp::class);
        $stamp->getEnvelope()->reject();
    }

    /**
     * {@inheritdoc}
     */
    public function send(Envelope $envelope): Envelope
    {
        $message = $envelope->getMessage();

        /** @var DestinationStamp|null $destinationStamp */
        $destinationStamp = $envelope->last(DestinationStamp::class) ?: new DestinationStamp();
        /** @var DelayStamp|null $delayStamp */
        $delayStamp = $envelope->last(DelayStamp::class);

        $queueMessage = new QueueMessage();
        $queueMessage->setData($message);
        $queueMessage->setName($destinationStamp->getMessageName() ?: get_class($message));
        $queueMessage->setMaxTries($destinationStamp->getMaxTries());
        $queueMessage->disableStore($destinationStamp->noStore());
        $queueMessage->setNeedsReply($destinationStamp->getNeedsReply());
        $queueMessage->setDelay($delayStamp !== null ? $delayStamp->getDelay() : 0);
        $queueMessage->setHeaders($destinationStamp->getHeaders());
        $queueMessage->addHeader('stamps', $this->serializeStamps($envelope));

        if ($queueMessage->needsReply()) {
            $result = $this->destination->send($queueMessage)->await($destinationStamp->getRpcTimeout());

            return $envelope->with(new HandledStamp($result, static::class));
        }

        $this->destination->send($queueMessage);

        return $envelope;
    }

    /**
     * Extract stamps from the envelope
     * The returned value is a serialization of a single dimension array
     */
    private function serializeStamps(Envelope $envelope): string
    {
        $stamps = $envelope->all();

        if (!empty($stamps)) {
            // Convert two dimension array (Envelope::all() return list of stamps, grouping by the stamp class)
            // Use array_values to remove the keys (class name) for get a sequential array
            $stamps = array_merge(...array_values($stamps));
        }

        return $this->stampsSerializer->serialize($stamps);
    }

    /**
     * Extract stamps from the queued envelope
     */
    private function unserializeStamps(QueuedEnvelope $queuedEnvelope): array
    {
        if ($stamps = $queuedEnvelope->message()->header('stamps')) {
            return $this->stampsSerializer->deserialize($stamps);
        }

        return [];
    }
}

