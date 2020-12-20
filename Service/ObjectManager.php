<?php declare(strict_types=1);

namespace UniMethod\Bundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use UniMethod\JsonapiMapper\External\ObjectManagerInterface;

class ObjectManager implements ObjectManagerInterface
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function loadByClassAndId(string $className, string $id): ?object
    {
        return $this->entityManager->getRepository($className)->find($id);
    }
}
