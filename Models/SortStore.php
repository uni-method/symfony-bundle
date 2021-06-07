<?php declare(strict_types=1);

namespace UniMethod\Bundle\Models;

class SortStore
{
    /**
     * @var Filter[]
     */
    protected array $sort;

    public function __construct(array $sort)
    {
        $this->sort = $sort;
    }

    public function all(): array
    {
        return $this->sort;
    }

    public function filterByName(string $name): ?Sort
    {
        $values = array_values(
            array_filter(
                $this->all(),
                static fn(Sort $sort) => $sort->name === $name
            )
        );

        if (count($values) === 1) {
            return $values[0];
        }

        return null;
    }
}
