<?php declare(strict_types=1);

namespace UniMethod\Bundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use UniMethod\Bundle\Service\PathResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use UniMethod\JsonapiMapper\Exception\ConfigurationException;
use UniMethod\JsonapiMapper\Service\Serializer;

class DeleteController
{
    protected PathResolver $pathResolver;
    protected Serializer $serializer;
    protected EntityManagerInterface $entityManager;

    public function __construct(
        PathResolver $pathResolver,
        Serializer $serializer,
        EntityManagerInterface $entityManager
    ) {
        $this->pathResolver = $pathResolver;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
    }

    /**
     * @return JsonResponse
     * @throws ConfigurationException
     */
    public function action(): JsonResponse
    {
        $alias = $this->pathResolver->getAlias();
        $class = $this->pathResolver->getConfigStore()->getEntityConfigByAlias($alias)->class;
        $item = $this->entityManager->getRepository($class)->find($this->pathResolver->getId());
        $this->entityManager->remove($item);
        $this->entityManager->flush();
        return new JsonResponse($this->serializer->handleObject($item));
    }
}
