<?php declare(strict_types=1);

namespace UniMethod\Bundle\Models;

class RouteStore
{
    /**
     * @var Route[]
     */
    protected array $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function all(): array
    {
        return $this->routes;
    }

    public function filterByAliasAndMethod(string $alias, string $method): ?Route
    {
        $values = array_values(
            array_filter(
                $this->all(),
                static fn(Route $route) => $route->modelAlias === $alias && $route->method === $method
            )
        );

        if (count($values) === 1) {
            return $values[0];
        }

        return null;
    }
}
