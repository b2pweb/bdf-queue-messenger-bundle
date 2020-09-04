<?php

namespace Bdf\QueueMessengerBundle\Transport\Stamp;

use Bdf\Queue\Message\EnvelopeInterface;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

/**
 * Stamp applied when a message is received from bdf-queue.
 */
class BdfQueueReceivedStamp implements NonSendableStampInterface
{
    private $envelope;

    public function __construct(EnvelopeInterface $envelope)
    {
        $this->envelope = $envelope;
    }

    public function getEnvelope(): EnvelopeInterface
    {
        return $this->envelope;
    }
}
