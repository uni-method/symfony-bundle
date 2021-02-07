<?php declare(strict_types=1);

namespace UniMethod\Bundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use UniMethod\Bundle\Service\PathResolver;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use UniMethod\Bundle\Service\ValidationService;
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
    protected ValidationService $validationService;

    public function __construct(
        PathResolver $pathResolver,
        Deserializer $deserializer,
        Serializer $serializer,
        EntityManagerInterface $entityManager,
        ValidationService $validationService
    ) {
        $this->pathResolver = $pathResolver;
        $this->deserializer = $deserializer;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->validationService = $validationService;
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

        $item = $this->updateObject(
            json_decode($this->pathResolver->getContent(), true, 512, JSON_THROW_ON_ERROR),
            $included
        );

        $errors = $this->validationService->validate($item);

        if (count($errors) > 0) {
            return new JsonResponse($this->serializer->handleErrors($errors));
        }

        $this->saveObject($item);

        return new JsonResponse($this->serializer->handleObject($item, $included));
    }

    /**
     * @param mixed $data
     * @param string $included
     * @return object
     * @throws BrokenInputException
     * @throws ConfigurationException
     */
    protected function updateObject(array $data, string $included): object
    {
        return $this->deserializer->handle(
            $data,
            Method::UPDATE,
            $included
        );
    }

    /**
     * Save object
     *
     * @param object $item
     */
    protected function saveObject(object $item): void
    {
        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }
}
