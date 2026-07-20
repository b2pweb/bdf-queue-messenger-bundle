<?php

namespace Bdf\QueueMessengerBundle\Tests\Functional\Fixtures;

/**
 * Records the messages it handles so the test can assert the full
 * dispatch -> transport -> worker -> handler round trip.
 */
class TestMessageHandler
{
    /**
     * @var TestMessage[]
     */
    public $handled = [];

    public function __invoke(TestMessage $message): void
    {
        $this->handled[] = $message;
    }
}
