<?php declare(strict_types=1);

namespace UniMethod\Bundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use UniMethod\JsonapiMapper\External\ContainerManagerInterface;

class ContainerManager implements ContainerManagerInterface
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $classOrAlias
     * @return object
     */
    public function getService(string $classOrAlias): object
    {
        return $this->container->get($classOrAlias);
    }
}
