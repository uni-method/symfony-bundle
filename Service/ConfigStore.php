<?php declare(strict_types=1);

namespace UniMethod\Bundle\Service;

class ConfigStore extends \UniMethod\JsonapiMapper\Config\ConfigStore
{
    /**
     * @inheritDoc
     */
    protected function getClassName(object $object): string
    {
        return str_replace('Proxies\__CG__\\', '', get_class($object));
    }
}
