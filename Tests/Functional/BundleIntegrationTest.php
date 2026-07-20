<?php

namespace Bdf\QueueMessengerBundle\Tests\Functional;

use Bdf\QueueMessengerBundle\Tests\Functional\Fixtures\TestKernel;
use Bdf\QueueMessengerBundle\Tests\Functional\Fixtures\TestMessage;
use Bdf\QueueMessengerBundle\Tests\Functional\Fixtures\TestMessageHandler;
use Bdf\QueueMessengerBundle\Transport\Handler\MessageBusHandler;
use Bdf\QueueMessengerBundle\Transport\QueueTransport;
use Bdf\QueueMessengerBundle\Transport\QueueTransportFactory;
use Bdf\QueueMessengerBundle\Transport\Stamp\BdfQueueReceivedStamp;
use Bdf\QueueMessengerBundle\Transport\Stamp\PhpStampsSerializer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\EventListener\StopWorkerOnMessageLimitListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Symfony\Component\Messenger\Worker;

/**
 * Boots a real Symfony container with the bundle registered and checks that its
 * services are wired and that the messenger transport works end to end against
 * an in-memory queue.
 */
class BundleIntegrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testTransportFactoryIsRegistered()
    {
        self::bootKernel();

        $factory = self::getContainer()->get('bdf_queue.messenger_transport.factory');

        $this->assertInstanceOf(QueueTransportFactory::class, $factory);
    }

    public function testDefaultStampSerializerIsThePhpOne()
    {
        self::bootKernel();

        $serializer = self::getContainer()->get('bdf_queue.messenger_transport.stamp_serializer');

        $this->assertInstanceOf(PhpStampsSerializer::class, $serializer);
    }

    public function testMessageBusHandlerIsRegistered()
    {
        self::bootKernel();

        $handler = self::getContainer()->get(MessageBusHandler::class);

        $this->assertInstanceOf(MessageBusHandler::class, $handler);
    }

    public function testMessengerBuildsAQueueTransportForBdfQueueDsn()
    {
        self::bootKernel();

        // The transport is built by symfony messenger using the tagged factory
        // of the bundle : getting a QueueTransport proves the factory is
        // properly tagged as "messenger.transport_factory".
        $transport = self::getContainer()->get('messenger.transport.bdf');

        $this->assertInstanceOf(QueueTransport::class, $transport);
    }

    public function testSendAndReceiveRoundTrip()
    {
        self::bootKernel();

        /** @var QueueTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.bdf');

        $message = new TestMessage('hello');
        $transport->send(new Envelope($message));

        $received = iterator_to_array($transport->get());

        $this->assertCount(1, $received);

        /** @var Envelope $envelope */
        $envelope = $received[0];
        $this->assertEquals($message, $envelope->getMessage());
        $this->assertNotNull($envelope->last(BdfQueueReceivedStamp::class));

        // Acknowledging must not throw and should leave the queue empty.
        $transport->ack($envelope);

        $this->assertSame([], iterator_to_array($transport->get()));
    }

    public function testDispatchThroughMessengerBusRoutesToTransportAndIsHandled()
    {
        self::bootKernel();

        $container = self::getContainer();

        /** @var MessageBusInterface $bus */
        $bus = $container->get('messenger.default_bus');
        /** @var TestMessageHandler $handler */
        $handler = $container->get(TestMessageHandler::class);

        $message = new TestMessage('via-bus');

        // Dispatch through the bus : the routing sends the message to the bdf
        // transport asynchronously, so it must NOT be handled in process yet.
        $envelope = $bus->dispatch($message);

        $this->assertNotNull($envelope->last(SentStamp::class), 'The message should have been sent to a transport');
        $this->assertSame([], $handler->handled, 'An async message must not be handled during dispatch');

        // Consume the transport with a real worker : it receives the message
        // from the queue and dispatches it back to the bus for handling.
        $worker = new Worker(
            ['bdf' => $container->get('messenger.transport.bdf')],
            $bus,
            $this->workerEventDispatcher()
        );
        $worker->run(['sleep' => 0]);

        $this->assertCount(1, $handler->handled);
        $this->assertEquals($message, $handler->handled[0]);
    }

    /**
     * Event dispatcher stopping the worker as soon as one message is handled,
     * with a wall-clock deadline as a safety net so the test can never hang.
     */
    private function workerEventDispatcher(): EventDispatcher
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new StopWorkerOnMessageLimitListener(1));

        $deadline = microtime(true) + 5;
        $dispatcher->addListener(WorkerRunningEvent::class, function (WorkerRunningEvent $event) use ($deadline) {
            if (microtime(true) >= $deadline) {
                $event->getWorker()->stop();
            }
        });

        return $dispatcher;
    }
}
