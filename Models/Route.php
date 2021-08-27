<?php declare(strict_types=1);

namespace UniMethod\Bundle\Models;

use Exception;
use UniMethod\Bundle\Controller\CreateAction;
use UniMethod\Bundle\Controller\DeleteAction;
use UniMethod\Bundle\Controller\ListAction;
use UniMethod\Bundle\Controller\UpdateAction;
use UniMethod\Bundle\Controller\ViewAction;
use UniMethod\JsonapiMapper\Config\Method;

class Route
{
    public string $modelAlias;
    public string $method;
    public ?string $action = null;
    public string $idConstraint = '\d+';
    public FilterStore $filters;
    public SortStore $sort;

    public function __construct(string $modelAlias, string $method)
    {
        $this->modelAlias = $modelAlias;
        $this->method = $method;
    }

    public function getPath(string $prefix, string $version): string
    {
        return '/' . $prefix . $version . '/' . $this->modelAlias . '/'
            . ($this->method !== Method::LIST && $this->method !== Method::CREATE ? '{id}' : '');
    }

    public function getRouteName(string $prefix, string $version): string
    {
        return $prefix . $version . '_' . $this->modelAlias . '_' . $this->method;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getHttpMethod(): string
    {
        switch ($this->method) {
            case Method::LIST:
            case Method::VIEW:
                return 'GET';
            case Method::CREATE:
                return 'POST';
            case Method::UPDATE:
                return 'PATCH';
            case Method::DELETE:
                return 'DELETE';
            default:
                throw new Exception(sprintf('Bad "%s" method for "%s"', $this->method, $this->modelAlias));
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getAction(): string
    {
        if ($this->action !== null) {
            return $this->action;
        }

        switch ($this->method) {
            case Method::LIST:
                return ListAction::class . '::action';
            case Method::VIEW:
                return ViewAction::class . '::action';
            case Method::CREATE:
                return CreateAction::class . '::action';
            case Method::UPDATE:
                return UpdateAction::class . '::action';
            case Method::DELETE:
                return DeleteAction::class . '::action';
            default:
                throw new Exception(sprintf('Bad "%s" method for "%s"', $this->method, $this->modelAlias));
        }
    }
}
