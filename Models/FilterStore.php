<?php declare(strict_types=1);

namespace UniMethod\Bundle\Models;

class FilterStore
{
    /**
     * @var Filter[]
     */
    protected array $filters;

    protected ?object $modelValidator = null;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function all(): array
    {
        return $this->filters;
    }

    public function filterByName(string $name): ?Filter
    {
        $values = array_values(
            array_filter(
                $this->all(),
                static fn(Filter $filter) => $filter->name === $name
            )
        );

        if (count($values) === 1) {
            return $values[0];
        }

        return null;
    }

    /**
     * @return object|null
     */
    public function getModelValidator(): ?object
    {
        return $this->modelValidator;
    }

    /**
     * @param object|null $modelValidator
     */
    public function setModelValidator(?object $modelValidator): void
    {
        $this->modelValidator = $modelValidator;
    }
}
