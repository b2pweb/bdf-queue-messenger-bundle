<?php

namespace Bdf\QueueMessengerBundle\Transport\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Handle serialisation and deserialization of envelope stamps
 *
 * The serialisation format is not defined (i.e. may be an array of a string),
 * but the same serializer must be used for serialize and deserialize
 */
interface StampsSerializerInterface
{
    /**
     * Serialize the stamps
     *
     * @param StampInterface[] $stamps
     *
     * @return mixed
     */
    public function serialize(array $stamps);

    /**
     * Extract stamps from the serialized value
     *
     * @param mixed $serialized
     *
     * @return StampInterface[]
     */
    public function deserialize($serialized): array;
}
