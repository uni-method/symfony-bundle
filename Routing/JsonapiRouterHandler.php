<?php declare(strict_types=1);

namespace UniMethod\Bundle\Routing;

use UniMethod\Bundle\Service\PathResolver;
use RuntimeException;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use UniMethod\JsonapiMapper\Config\Method;

class JsonapiRouterHandler extends Loader
{
    private bool $isLoaded = false;

    protected PathResolver $pathResolver;

    public function __construct(PathResolver $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }

    public function load($resource, string $type = null): RouteCollection
    {
        if (true === $this->isLoaded) {
            throw new RuntimeException('Do not add the "API" loader twice');
        }

        $prefix = $this->pathResolver->getPrefix();
        $routes = new RouteCollection();

        $versions = $this->pathResolver->getAvailableVersions();

        foreach ($versions as $version) {
            $config = $this->pathResolver->getRoutesByVersion($version)->all();
            foreach ($config as $value) {
                $path = $value->getPath($prefix, $version);
                $routeName = $value->getRouteName($prefix, $version);
                $method = $value->getHttpMethod();
                $defaults = [
                    '_controller' => $value->getAction(),
                ];
                $requirements = [];

                if ($value->method === Method::VIEW
                    || $value->method === Method::UPDATE
                    || $value->method === Method::DELETE) {
                    $requirements = [
                        'parameter' => $value->idConstraint,
                    ];
                }

                $route = new Route($path, $defaults, $requirements, [], null, [], [$method]);
                $routes->add($routeName, $route);
            }
        }

        $this->isLoaded = true;
        return $routes;
    }

    public function supports($resource, string $type = null): bool
    {
        return 'api' === $type;
    }
}
