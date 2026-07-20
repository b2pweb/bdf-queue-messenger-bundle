<?php

namespace Bdf\QueueMessengerBundle\Tests\Functional\Fixtures;

use Bdf\Queue\Connection\Factory\CachedConnectionDriverFactory;
use Bdf\Queue\Connection\Factory\ResolverConnectionDriverFactory;
use Bdf\Queue\Connection\Memory\MemoryConnection;
use Bdf\Queue\Destination\ConfigurationDestinationFactory;
use Bdf\Queue\Destination\DestinationManager;
use Bdf\Queue\Destination\DsnDestinationFactory;

/**
 * Build a "real" destination manager backed by an in-memory connection.
 *
 * This service is normally provided by the bdf-queue framework bundle. It is
 * redefined here so the messenger bundle can be booted in a real container
 * without pulling the whole queue bundle, while still exercising the actual
 * destination/consumer stack (no mock).
 */
final class DestinationManagerFactory
{
    public static function create(): DestinationManager
    {
        $connectionFactory = new ResolverConnectionDriverFactory([
            'memory-queue' => 'memory://localhost',
        ]);
        $connectionFactory->addDriverResolver('memory', function () {
            return new MemoryConnection();
        });

        // Cache the connections so a message sent by the transport and the
        // consumer that reads it back share the same in-memory storage.
        $connectionFactory = new CachedConnectionDriverFactory($connectionFactory);

        return new DestinationManager(
            $connectionFactory,
            new ConfigurationDestinationFactory([], new DsnDestinationFactory($connectionFactory))
        );
    }
}
