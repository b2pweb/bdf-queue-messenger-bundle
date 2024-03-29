<?php

namespace Bdf\QueueMessengerBundle\Transport\Stamp;

/**
 * Serializer using native PHP serialize methods.
 *
 * Note: This serializer is unsecured, and must not be used on public channels
 */
final class PhpStampsSerializer implements StampsSerializerInterface
{
    public function serialize(array $stamps)
    {
        return serialize($stamps);
    }

    public function deserialize($serialized): array
    {
        return unserialize($serialized);
    }
}
