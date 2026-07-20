<?php

namespace Bdf\QueueMessengerBundle\Tests\Functional\Fixtures;

use Bdf\Queue\Destination\DestinationManager;
use Bdf\QueueMessengerBundle\BdfQueueMessengerBundle;
use Bdf\QueueMessengerBundle\Transport\Handler\MessageBusHandler;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Minimal kernel booting the framework bundle together with the messenger
 * bundle under test.
 */
class TestKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new BdfQueueMessengerBundle(),
        ];
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(
            new PublicServicesPass([
                MessageBusHandler::class,
                'bdf_queue.messenger_bus_handler',
                'messenger.default_bus',
            ]),
            PassConfig::TYPE_BEFORE_REMOVING
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) {
            $config = [
                'test' => true,
                'secret' => 'test',
                'http_method_override' => false,
                'php_errors' => ['log' => true],
                'messenger' => [
                    'transports' => [
                        'bdf' => 'bdfqueue://memory-queue',
                    ],
                    'routing' => [
                        TestMessage::class => ['bdf'],
                    ],
                ],
            ];

            // Introduced in Symfony 6.4, unknown (and thus rejected) on 5.4.
            if (Kernel::VERSION_ID >= 60400) {
                $config['handle_all_throwables'] = true;
            }

            $container->loadFromExtension('framework', $config);

            // Service usually provided by the bdf-queue framework bundle.
            $container
                ->register('bdf_queue.destination_manager', DestinationManager::class)
                ->setFactory([DestinationManagerFactory::class, 'create'])
                ->setPublic(true);

            // Handler used to assert the dispatch -> transport -> worker flow.
            $container
                ->register(TestMessageHandler::class, TestMessageHandler::class)
                ->addTag('messenger.message_handler')
                ->setPublic(true);
        });
    }

    public function getCacheDir(): string
    {
        return $this->buildDir().'/cache';
    }

    public function getBuildDir(): string
    {
        return $this->buildDir().'/build';
    }

    public function getLogDir(): string
    {
        return $this->buildDir().'/log';
    }

    private function buildDir(): string
    {
        return sys_get_temp_dir().'/bdf_queue_messenger_bundle_tests/'.$this->environment;
    }
}
