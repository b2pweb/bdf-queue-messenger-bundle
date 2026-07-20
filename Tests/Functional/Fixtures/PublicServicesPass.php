<?php

namespace Bdf\QueueMessengerBundle\Tests\Functional\Fixtures;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Force some bundle services to stay public so they can be fetched from the
 * test container and are not removed as "unused" in the minimal test kernel.
 */
final class PublicServicesPass implements CompilerPassInterface
{
    /**
     * @var string[]
     */
    private $ids;

    /**
     * @param string[] $ids
     */
    public function __construct(array $ids)
    {
        $this->ids = $ids;
    }

    public function process(ContainerBuilder $container): void
    {
        foreach ($this->ids as $id) {
            if ($container->hasDefinition($id)) {
                $container->getDefinition($id)->setPublic(true);
            } elseif ($container->hasAlias($id)) {
                $container->getAlias($id)->setPublic(true);
            }
        }
    }
}
