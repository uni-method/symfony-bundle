<?php declare(strict_types=1);

namespace UniMethod\Bundle\Routing;

use UniMethod\Bundle\Controller\CreateAction;
use UniMethod\Bundle\Controller\DeleteAction;
use UniMethod\Bundle\Controller\ListAction;
use UniMethod\Bundle\Controller\UpdateAction;
use UniMethod\Bundle\Controller\ViewAction;
use UniMethod\Bundle\Service\PathResolver;
use RuntimeException;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use UniMethod\JsonapiMapper\Config\Method;

class JsonapiRouterHandler extends Loader
{
    private bool $isLoaded = false;

    protected PathResolver $pathResolver;

    public function __construct(
        PathResolver $pathResolver
    )
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
            $config = $this->pathResolver->getRoutesByVersion($version);
            foreach ($config as $value) {
                $path = '/' . $prefix . $version . '/' . $value['item'] . '/';
                $routeName = $prefix . $version . '_' . $value['item'] . '_' . $value['method'];
                if ($value['method'] === Method::LIST) {
                    $action = ListAction::class . '::action';
                    if (!empty($value['action'])) {
                        $action = $value['action'];
                    }
                    $defaults = [
                        '_controller' => $action,
                    ];
                    $requirements = [];
                    $method = 'GET';
                } elseif ($value['method'] === Method::VIEW) {
                    $action = ViewAction::class . '::action';
                    if (!empty($value['action'])) {
                        $action = $value['action'];
                    }
                    $defaults = [
                        '_controller' => $action,
                    ];
                    $requirements = [
                        'parameter' => $value['id'] ?? '\d+',
                    ];
                    $method = 'GET';
                    $path .= '{id}';
                } elseif ($value['method'] === Method::CREATE) {
                    $action = CreateAction::class . '::action';
                    if (!empty($value['action'])) {
                        $action = $value['action'];
                    }
                    $defaults = [
                        '_controller' => $action,
                    ];
                    $requirements = [];
                    $method = 'POST';
                } elseif ($value['method'] === Method::UPDATE) {
                    $action = UpdateAction::class . '::action';
                    if (!empty($value['action'])) {
                        $action = $value['action'];
                    }
                    $defaults = [
                        '_controller' => $action,
                    ];
                    $requirements = [];
                    $method = 'PATCH';
                    $path .= '{id}';
                } elseif ($value['method'] === Method::DELETE) {
                    $action = DeleteAction::class . '::action';
                    if (!empty($value['action'])) {
                        $action = $value['action'];
                    }
                    $defaults = [
                        '_controller' => $action,
                    ];
                    $requirements = [];
                    $method = 'DELETE';
                    $path .= '{id}';
                } else {
                    throw new NotFoundHttpException();
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
