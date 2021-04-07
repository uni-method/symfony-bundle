<?php declare(strict_types=1);

namespace UniMethod\Bundle\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Yaml\Yaml;

class PathResolver
{
    protected YamlLoader $yamlLoader;
    protected ParameterBagInterface $parameterBag;
    protected ?Request $request;

    public function __construct(
        YamlLoader $yamlLoader,
        ParameterBagInterface $parameterBag,
        RequestStack $request
    ) {
        $this->yamlLoader = $yamlLoader;
        $this->parameterBag = $parameterBag;
        $this->request = $request->getCurrentRequest();
    }

    public function getConfigStore(): ConfigStore
    {
        return $this->yamlLoader->load($this->getPath());
    }

    public function getRoutesByVersion(string $version): array
    {
        return Yaml::parseFile($this->parameterBag->get('jsonapi-default_path') . '/' . $version . '/config.yml')['paths'] ?? [];
    }

    public function getAvailableVersions(): array {
        return array_map('strval', $this->parameterBag->get('jsonapi-available'));
    }

    public function getPrefix(): string
    {
        return $this->parameterBag->get('jsonapi-prefix');
    }

    public function getVersion(): string
    {
        if ($this->request === null) {
            return '';
        }
        return $this->extractFromUrl($this->request->getPathInfo(), 1, 3);
    }

    public function getAlias(): string
    {
        if ($this->request === null) {
            return '';
        }
        $url = substr($this->request->getPathInfo(), strlen('/' . $this->getPrefix() . $this->getVersion() . '/'));
        return explode('/', $url, 2)[0] ?? '';
    }

    public function getId(): string
    {
        if ($this->request === null) {
            return '';
        }

        $url = substr($this->request->getPathInfo(), strlen('/' . $this->getPrefix() . $this->getVersion() . '/' . $this->getAlias() . '/'));
        return explode('/', $url, 2)[0] ?? '';
    }

    public function getIncluded(): string
    {
        if ($this->request === null) {
            return '';
        }
        return $this->request->query->get('included', '');
    }

    public function getPath(): string
    {
        return $this->parameterBag->get('jsonapi-default_path') . '/' . $this->getVersion();
    }

    public function getContent(): string
    {
        return $this->request->getContent();
    }

    /**
     * Get filters for filter collection
     *
     * @return mixed[]
     */
    public function getFilters(): array
    {
        return $this->request->get('filters', []);
    }

    /**
     * Get sort for sort collection
     *
     * @return mixed[]
     */
    public function getSort(): array
    {
        return $this->request->get('sort', []);
    }

    /**
     * @return mixed
     */
    public function getPagination(): array
    {
        return $this->request->get('page', []);
    }

    protected function extractFromUrl(string $url, int $index, int $limit): string
    {
        $prefix = $this->getPrefix();
        $rawVersion = explode('/', $url, $limit)[$index] ?? '';
        $part = '';
        if (strpos($rawVersion, $prefix) === 0) {
            $part = substr($rawVersion, strlen($prefix));
        }
        return $part;
    }
}
