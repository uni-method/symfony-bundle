<?php

declare(strict_types=1);

namespace UniMethod\Bundle\Service;

use UniMethod\JsonapiMapper\External\Error;

/**
 * For implementing validation override service
 */
class ValidationService
{
    /**
     * @param object $item
     * @return Error[]
     */
    public function validate(object $item): array
    {
        return [];
    }
}
