<?php declare(strict_types=1);

namespace UniMethod\Bundle\Controller;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use UniMethod\Bundle\Models\FilterStore;
use UniMethod\Bundle\Service\PathResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use UniMethod\Bundle\Service\ValidationService;
use UniMethod\JsonapiMapper\Config\Method;
use UniMethod\JsonapiMapper\Exception\ConfigurationException;
use UniMethod\JsonapiMapper\Service\Serializer;

class ListAction implements ActionInterface
{
    /**
     * Accepted expressions
     */
    protected const ACCEPTED_EXPRESSIONS = [
        'contains',
        'eq',
        'gt',
        'gte',
        'lt',
        'lte',
    ];

    protected PathResolver $pathResolver;
    protected Serializer $serializer;
    protected EntityManagerInterface $entityManager;
    protected ValidationService $validationService;

    public function __construct(
        PathResolver $pathResolver,
        Serializer $serializer,
        EntityManagerInterface $entityManager,
        ValidationService $validationService
    ) {
        $this->pathResolver = $pathResolver;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->validationService = $validationService;
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
        $criteriaArr = [];

        $route = $this->pathResolver->getRoutes()->filterByAliasAndMethod($this->getAlias(), Method::LIST);

        if ($route === null) {
            return [];
        }

        $rawFilter = $this->pathResolver->getRawFilter();
        $modelValidator = $route->filters->getModelValidator();

        if ($modelValidator !== null) {
            $filterForValidation = $this->prepareFilter($route->filters, $rawFilter);
            $models = $this->getFilterModelsForValidate($filterForValidation, $modelValidator);
            $errors = [];

            foreach ($models as $model) {
                foreach ($this->validationService->validate($model) as $error) {
                    $errors[] = $error;
                }
            }

            if (count($errors) > 0) {
                $exception = new ValidationException();
                $exception->errors = $errors;
                throw $exception;
            }
        }

        foreach ($rawFilter as $property => $params) {
            $expressions = [];

            $filter = $route->filters->filterByName($property);

            if ($filter !== null) {
                foreach ($params as $expression => $value) {
                    $expression = mb_strtolower($expression);
                    if (in_array($expression, self::ACCEPTED_EXPRESSIONS, true)) {
                        $expressions[] = $this->makeFilterExpression($expression, $filter->alias, $value);
                    }
                }
            }

            if ($expressions !== []) {
                $criteriaArr[] = Criteria::create()->andWhere(Criteria::expr()->andX(...$expressions));
            }
        }

        $sorts = [];

        foreach ($this->pathResolver->getRawSort() as $property => $sortValue) {
            $sort = $route->sort->filterByName($property);

            if ($sort !== null) {
                $sorts[] = [$sort->alias => $sortValue];
            }
        }

        if ($sorts !== []) {
            $criteriaArr[] = Criteria::create()->orderBy(array_merge(...$sorts));
        }

        return $criteriaArr;
    }

    protected function prepareFilter(FilterStore $filterStore, array $filters): array
    {
        if ($filters === []) {
            return [];
        }

        $result = [];

        foreach ($filters as $property => $value) {
            $filter = $filterStore->filterByName($property);
            if ($filter !== null) {
                $result[$filter->alias] = array_values($value);
            }
        }

        return $result;
    }

    protected function getFilterModelsForValidate(array $filters, object $sourceObject): array
    {
        return array_reduce(array_keys($filters), function (array $objects, string $property) use ($filters, $sourceObject) {
            $values = $filters[$property];

            if (count($objects) === 0) {
                foreach ($values as $value) {
                    $object = clone $sourceObject;
                    $object->$property = $value;
                    $objects[] = $object;

                }
            } else {
                $newObjects = [];
                foreach ($objects as $object) {
                    if (count($values) === 1) {
                        $object->$property = $values[0];
                        $newObjects[] = $object;
                    } else {
                        foreach ($values as $value) {
                            $newObject = clone $object;
                            $newObject->$property = $value;
                            $newObjects[] = $newObject;
                        }
                    }
                }
                $objects = $newObjects;
            }

            return $objects;
        }, []);
    }

    /**
     * @param string $expression
     * @param string $field
     * @param mixed $value
     * @return Expression
     */
    protected function makeFilterExpression(string $expression, string $field, $value): Expression
    {
        return Criteria::expr()->$expression($this->getAlias() . '.' . $field, $value);
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
        return (int)($pagination['limit'] ?? Page::DEFAULT_LIMIT);
    }

    /**
     * @return int
     */
    protected function getOffset(): int
    {
        $pagination = $this->pathResolver->getPagination();
        return (int)($pagination['offset'] ?? Page::DEFAULT_OFFSET);
    }
}
