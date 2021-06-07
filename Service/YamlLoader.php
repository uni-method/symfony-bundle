<?php declare(strict_types=1);

namespace UniMethod\Bundle\Service;

use Symfony\Component\Yaml\Yaml;
use UniMethod\Bundle\Models\Filter;
use UniMethod\Bundle\Models\FilterStore;
use UniMethod\Bundle\Models\Route;
use UniMethod\Bundle\Models\RouteStore;
use UniMethod\Bundle\Models\Sort;
use UniMethod\Bundle\Models\SortStore;
use UniMethod\JsonapiMapper\Config\AttributeConfig;
use UniMethod\JsonapiMapper\Config\EntityConfig;
use UniMethod\JsonapiMapper\Config\Event;
use UniMethod\JsonapiMapper\Config\RelationshipConfig;
use UniMethod\JsonapiMapper\External\ConfigLoaderInterface;
use UniMethod\JsonapiMapper\External\ContainerManagerInterface;

class YamlLoader implements ConfigLoaderInterface
{
    /** @var ContainerManagerInterface */
    protected ContainerManagerInterface $containerManager;

    public function __construct(ContainerManagerInterface $containerManager)
    {
        $this->containerManager = $containerManager;
    }

    public function load(string $path): ConfigStore
    {
        $entityConfig = Yaml::parseFile($path . '/config.yml');
        $entities = [];
        foreach ($entityConfig['entities'] as $alias => $item) {
            $entity = (new EntityConfig())
                ->setDescription($item['description'])
                ->setAlias($alias)
                ->setClass($item['class']);

            if ($item['type']) {
                $entity->type = $item['type'];
            }

            $entity->setPostLoadHandlers(array_map(function ($classOrAlias) {
                return $this->containerManager->getService($classOrAlias);
            }, $item[Event::POST_LOAD] ?? []));

            $entity->setPreCreateHandlers(array_map(function ($classOrAlias) {
                return $this->containerManager->getService($classOrAlias);
            }, $item[Event::PRE_CREATE] ?? []));

            $attributes = [];
            $attributesRaw = $item['attributes'] ?? [];
            foreach ($attributesRaw as $internalType => $attribute) {
                $attributes[] = new AttributeConfig($internalType, $attribute['type'], $attribute['setter'] ?? null, $attribute['getter'] ?? null);
            }

            $relationships = [];
            $relationshipsRaw = $item['relationships'] ?? [];
            foreach ($relationshipsRaw as $internalType => $relationship) {
                $relationships[] = new RelationshipConfig($internalType, $relationship['type'], $relationship['setter'] ?? null, $relationship['getter'] ?? null);
            }

            $entity->setAttributes($attributes)->setRelationships($relationships);
            $entities[] = $entity;
        }
        return new ConfigStore($entities);
    }

    public function loadRoutes(string $path): RouteStore
    {
        $routes = [];
        $routeConfig = Yaml::parseFile($path)['paths'] ?? [];

        foreach ($routeConfig as $config) {
            $route = new Route($config['item'], $config['method']);

            if (!empty($config['action'])) {
                $route->action = $config['action'];
            }

            if (!empty($config['id'])) {
                $route->idConstraint = $config['id'];
            }

            $filters = [];

            if (!empty($config['filters']) && !empty($config['filters']['list'])) {
                foreach ($config['filters']['list'] as $filterConfig) {
                    if (is_array($filterConfig)) {
                        $name = array_key_first($filterConfig);
                        $alias = $filterConfig[$name]['alias'] ?? $name;
                    } else {
                        $name = $filterConfig;
                        $alias = $filterConfig;
                    }

                    $filter = new Filter($name, $alias);
                    $filters[] = $filter;
                }
            }

            $route->filters = new FilterStore($filters);

            if (!empty($config['filters']) && !empty($config['filters']['model']) && class_exists($config['filters']['model'])) {
                $route->filters->setModelValidator(new $config['filters']['model']);
            }

            $sortArr = [];

            if (!empty($config['sort']) && !empty($config['sort']['list'])) {
                foreach ($config['sort']['list'] as $sortConfig) {
                    if (is_array($sortConfig)) {
                        $name = array_key_first($sortConfig);
                        $alias = $sortConfig[$name]['alias'] ?? '';
                    } else {
                        $name = $sortConfig;
                        $alias = $sortConfig;
                    }
                    $sortArr[] = new Sort($name, $alias);
                }
            }

            $route->sort = new SortStore($sortArr);

            $routes[] = $route;
        }

        return new RouteStore($routes);
    }
}
