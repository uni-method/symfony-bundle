<?php declare(strict_types=1);

namespace UniMethod\Bundle\Models;

class Sort
{
    public string $name;
    public string $alias;

    public function __construct(string $name, string $alias)
    {
        $this->name = $name;
        $this->alias = $alias;
    }
}
