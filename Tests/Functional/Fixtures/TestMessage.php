<?php

namespace Bdf\QueueMessengerBundle\Tests\Functional\Fixtures;

/**
 * Simple serializable message used to check the transport round trip.
 */
final class TestMessage
{
    /**
     * @var string
     */
    public $content;

    public function __construct(string $content = '')
    {
        $this->content = $content;
    }
}
