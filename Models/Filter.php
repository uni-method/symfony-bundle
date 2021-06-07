<?php declare(strict_types=1);

namespace UniMethod\Bundle\Models;

class Filter
{
    public string $name;
    public string $alias;
    public ?string $type = null;

    public function __construct(string $name, string $alias)
    {
        $this->name = $name;
        $this->alias = $alias;
    }
}
