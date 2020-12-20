<?php declare(strict_types=1);

namespace UniMethod\Bundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use UniMethod\Bundle\Service\PathResolver;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use UniMethod\JsonapiMapper\Config\Method;
use UniMethod\JsonapiMapper\Exception\BrokenInputException;
use UniMethod\JsonapiMapper\Exception\ConfigurationException;
use UniMethod\JsonapiMapper\Service\Deserializer;
use UniMethod\JsonapiMapper\Service\Serializer;

class UpdateController implements ActionInterface
{
    protected PathResolver $pathResolver;
    protected Deserializer $deserializer;
    protected Serializer $serializer;
    protected EntityManagerInterface $entityManager;

    public function __construct(
        PathResolver $pathResolver,
        Deserializer $deserializer,
        Serializer $serializer,
        EntityManagerInterface $entityManager
    ) {
        $this->pathResolver = $pathResolver;
        $this->deserializer = $deserializer;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
    }

    /**
     * @return JsonResponse
     * @throws BrokenInputException
     * @throws ConfigurationException
     * @throws JsonException
     */
    public function action(): JsonResponse
    {
        $included = $this->pathResolver->getIncluded();
        $item = $this->deserializer->handle(
            json_decode($this->pathResolver->getContent(), true, 512, JSON_THROW_ON_ERROR),
            Method::UPDATE,
            $included
        );
        $this->entityManager->persist($item);
        $this->entityManager->flush();
        return new JsonResponse($this->serializer->handleObject($item, $included));
    }
}
