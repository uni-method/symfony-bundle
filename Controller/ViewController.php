<?php declare(strict_types=1);

namespace UniMethod\Bundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use UniMethod\Bundle\Service\PathResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use UniMethod\JsonapiMapper\Exception\ConfigurationException;
use UniMethod\JsonapiMapper\Service\Serializer;

class ViewController implements ActionInterface
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
        return new JsonResponse($this->serializer->handleObject($this->getObject(), $this->pathResolver->getIncluded()));
    }

    /**
     * @return ObjectRepository
     * @throws ConfigurationException
     */
    protected function initRepository(): ObjectRepository
    {
        $class = $this->pathResolver->getConfigStore()->getEntityConfigByAlias($this->getAlias())->class;
        return $this->entityManager->getRepository($class);
    }

    /**
     * @return object
     * @throws ConfigurationException
     */
    protected function getObject(): object
    {
        if ($this->serializer->isSynthetic($this->getAlias())) {
            throw new ConfigurationException('Please override getObject() function for synthetic model');
        }
        return $this->initRepository()->find($this->getId());
    }

    /**
     * @return string
     */
    protected function getAlias(): string
    {
        return $this->pathResolver->getAlias();
    }

    /**
     * @return string
     */
    protected function getId(): string
    {
        return $this->pathResolver->getId();
    }
}
