<?php declare(strict_types=1);

namespace UniMethod\Bundle\Controller;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use UniMethod\Bundle\Service\PathResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use UniMethod\JsonapiMapper\Exception\ConfigurationException;
use UniMethod\JsonapiMapper\Service\Serializer;

class ListAction implements ActionInterface
{
    protected const DEFAULT_LIMIT = 20;
    protected const DEFAULT_OFFSET = 0;

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
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws QueryException
     */
    public function action(): JsonResponse
    {
        return new JsonResponse(
            $this->serializer->handleCollection(
                $this->getCollection(),
                $this->pathResolver->getIncluded(),
                $this->getMeta()
            )
        );
    }

    /**
     * @return array
     * @throws ConfigurationException
     * @throws QueryException
     */
    protected function getCollection(): array
    {
        if ($this->serializer->isSynthetic($this->getAlias())) {
            throw new ConfigurationException('Please override getCollection() function for synthetic model');
        }
        return $this->findAllByCriteria(
            $this->initRepository(),
            array_merge($this->getCriteria(), [$this->getPaginationCriteria()])
        );
    }

    /**
     * @return mixed[]
     * @throws ConfigurationException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws QueryException
     */
    protected function getMeta(): array
    {
        if ($this->serializer->isSynthetic($this->getAlias())) {
            throw new ConfigurationException('Please override getMeta() function for synthetic model');
        }
        return [
            'count' => $this->getCountByCriteria($this->initRepository(), $this->getCriteria()),
            'limit' => $this->getLimit(),
            'offset' => $this->getOffset(),
        ];
    }

    /**
     * @param ObjectRepository $repository
     * @param Criteria[] $arr
     * @return mixed
     * @throws QueryException
     */
    protected function findAllByCriteria(ObjectRepository $repository, array $arr)
    {
        return $this->getBuilderByCriteria($repository, $arr)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return string
     */
    protected function getAlias(): string
    {
        return $this->pathResolver->getAlias();
    }

    /**
     * @param ObjectRepository $repository
     * @param array $arr
     * @return QueryBuilder
     * @throws QueryException
     */
    protected function getBuilderByCriteria(ObjectRepository $repository, array $arr): QueryBuilder
    {
        $builder = $repository->createQueryBuilder($this->getAlias());
        foreach ($arr as $criteria) {
            $builder->addCriteria($criteria);
        }
        return $builder;
    }

    /**
     * @param ObjectRepository $repository
     * @param array $arr
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws QueryException
     */
    public function getCountByCriteria(ObjectRepository $repository, array $arr): int
    {
        return (int)$this->getBuilderByCriteria($repository, $arr)
            ->select('count(' . $this->getAlias() . '.id)')
            ->getQuery()
            ->getSingleScalarResult();
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
     * @return Criteria
     */
    protected function getPaginationCriteria(): Criteria
    {
        $limit = $this->getLimit();
        $offset = $this->getOffset();

        $criteria = Criteria::create();
        $criteria->setMaxResults($limit)->setFirstResult($offset);

        return $criteria;
    }

    /**
     * @return Criteria[]
     */
    protected function getCriteria(): array
    {
        return [];
    }

    /**
     * @return mixed[]
     */
    protected function getSort(): array
    {
        return $this->pathResolver->getSort();
    }

    /**
     * @return mixed[]
     */
    protected function getFilters(): array
    {
        return $this->pathResolver->getFilters();
    }

    /**
     * @return int
     */
    protected function getLimit(): int
    {
        $pagination = $this->pathResolver->getPagination();
        return (int) ($pagination['limit'] ?? self::DEFAULT_LIMIT);
    }

    /**
     * @return int
     */
    protected function getOffset(): int
    {
        $pagination = $this->pathResolver->getPagination();
        return (int) ($pagination['offset'] ?? self::DEFAULT_OFFSET);
    }
}
