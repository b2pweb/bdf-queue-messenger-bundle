<?php

namespace Bdf\QueueMessengerBundle\Transport\Stamp;

/**
 * Do not serialize nor deserialize stamps.
 *
 * This serializer should be used on cross applications channels, and it's by nature totally safe
 */
final class NullStampsSerializer implements StampsSerializerInterface
{
    public function serialize(array $stamps)
    {
        return null;
    }

    public function deserialize($serialized): array
    {
        return [];
    }
}
