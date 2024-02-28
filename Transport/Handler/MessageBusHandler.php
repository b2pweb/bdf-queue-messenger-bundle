<?php

namespace Bdf\QueueMessengerBundle\Transport\Handler;

use Bdf\Queue\Message\EnvelopeInterface as QueuedEnvelope;
use Bdf\Queue\Message\InteractEnvelopeInterface;
use Bdf\QueueMessengerBundle\Transport\Stamp\NullStampsSerializer;
use Bdf\QueueMessengerBundle\Transport\Stamp\StampsSerializerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * Job for dispatch a queued command to the bus
 * If the message do not contains a valid object as data, an InternalMessage is dispatched.
 *
 * If the a reply is requested on the message,
 * a synchronised dispatch is performed,
 * and the result is returned as reply
 */
class MessageBusHandler
{
    /**
     * The command dispatcher.
     *
     * @var MessageBusInterface
     */
    private $dispatcher;

    /**
     * @var StampsSerializerInterface
     */
    private $stampsSerializer;

    /**
     * Constructor.
     */
    public function __construct(MessageBusInterface $dispatcher, ?StampsSerializerInterface $stampsSerializer = null)
    {
        $this->dispatcher = $dispatcher;
        $this->stampsSerializer = $stampsSerializer ?: new NullStampsSerializer();
    }

    /**
     * Handle a queued command.
     */
    public function __invoke($message, QueuedEnvelope $queuedEnvelope)
    {
        $envelope = $this
            ->toEnvelope($message, $queuedEnvelope)
            // Symfony 4.3 add transport name in constructor
            ->with(new ReceivedStamp($queuedEnvelope->connection()->getName()))
        ;

        $this->dispatch($envelope, $queuedEnvelope);
    }

    /**
     * Dispatch the envelope to the bus.
     *
     * If the message is replyable and a reply is requested, a synchronized call is performed and the result is returned
     */
    private function dispatch(Envelope $envelope, QueuedEnvelope $queuedEnvelope): void
    {
        $envelope = $this->dispatcher->dispatch($envelope);

        if ($queuedEnvelope instanceof InteractEnvelopeInterface && $queuedEnvelope->message()->needsReply()) {
            /** @var HandledStamp[] $handledStamps */
            $handledStamps = $envelope->all(HandledStamp::class);

            if (!$handledStamps) {
                throw new LogicException(sprintf('Message of type "%s" was handled zero times. Exactly one handler is expected when using "%s::%s()".', \get_class($envelope->getMessage()), \get_class($this), __FUNCTION__));
            }

            $queuedEnvelope->reply($handledStamps[0]->getResult());
        }
    }

    /**
     * Get the envelope from the message payload.
     */
    private function toEnvelope($message, QueuedEnvelope $queuedEnvelope): Envelope
    {
        if ($message instanceof Envelope) {
            return $message;
        }

        // TODO How to handle non object message
        //        if (!is_object($message)) {
        //            $message = new InternalMessage(
        //                $queuedEnvelope->message()->name() ?: $queuedEnvelope->message()->queue(),
        //                $message
        //            );
        //        }

        if ($stamps = $queuedEnvelope->message()->header('stamps')) {
            return new Envelope($message, $this->stampsSerializer->deserialize($stamps));
        }

        return new Envelope($message);
    }
}
